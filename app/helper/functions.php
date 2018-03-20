<?php

namespace App\helper;

use App\libraries\Validator;
use App\middleware\LoggerMiddleWare;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Class Functions
 * @package App\helper
 */
class Functions{


    /**
     * @param null $content
     * @param null $status
     * @param null $message
     * @return array
     */
    public static function output($content = null, $status = null, $message = null){
        $output = array();
        if(!empty($content)){
            $output = array_merge($output, array('data' => $content));
        }
        if(!empty($status)){
            $output = array_merge($output, array('status' => $status));
        }
        if(!empty($message)){
            $output = array_merge($output, array('message' => $message));
        }
        return $output;
    }


    /**
     * @param $offset
     * @param $limit
     * @param $total
     * @return array
     */
    public static function generateMetaData($offset, $limit, $total){
        $metadata = array('offset' => $offset, 'limit' => $limit, 'total_records' => $total);
        return $metadata;
    }


    /**
     * @param $output
     * @param Response $response
     * @param $responseStatusCode
     * @return mixed
     */
    public static function generateResponse($output, Response $response, $responseStatusCode){
        //Log anything which is not ok (200)
        if($responseStatusCode !== HTTP_STATUS_OK) {
            $logger = new LoggerMiddleWare();
            $logger->generateResponseLog($output, $logger::ERROR);
        }
        return $response->withJson($output, $responseStatusCode);
    }


    /**
     * @param $headers | $request->getHeaders()
     * @param $user | User Model (array)
     * @return string | encrypted
     */
    public static function generateAuthenticationToken($headers, $user){
        $authenticationToken = Functions::getIPAddress() . '@' . $headers['HTTP_USER_AGENT'][0] . '@' . time() . '@' . $headers['Host'][0] . '@' . $user['ID'] . '@' . $user['username'];
        $encrypt = new Encryption(DATA_ENCRYPTION_KEY);
        $encryptedAuthToken = $encrypt->encrypt($authenticationToken);
        return $encryptedAuthToken;
    }


    /**
     * Method to return response without body
     *
     * @param Response $response
     * @param $methods
     * @return mixed
     */
    public static function generateOptionsResponse(Response $response, $methods){
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Auth-Token, Content-Type, Accept, Origin')
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $methods))
            ->withStatus(HTTP_STATUS_OK);
    }


    /**
     * @param $content
     * @param $errorType
     */
    public static function generateGenericLog($content, $errorType){
        $logger = new LoggerMiddleWare();
        $level = $logger::INFO;
        switch($errorType){
            case 'error':
                $level = $logger::ERROR;
                break;
        }
        $logger->generateGenericLog($content, $level);
    }


    /**
     * @return string
     */
    public static function getIPAddress(){
        foreach (array('HTTP_CLIENT_IP',
                     'HTTP_X_FORWARDED_FOR',
                     'HTTP_X_FORWARDED',
                     'HTTP_X_CLUSTER_CLIENT_IP',
                     'HTTP_FORWARDED_FOR',
                     'HTTP_FORWARDED',
                     'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $IPaddress){
                    $IPaddress = trim($IPaddress); // Just to be safe
                    if (filter_var($IPaddress,
                            FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                        !== false) {
                        return $IPaddress;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'];
    }


    /**
     * @return array|false|string
     */
    public static function getEnvironment(){
        return getenv('ENVIRONMENT');
    }


    /**
     * @param $data | muldimensional array
     * @return string
     */
    public static function safeSerialize($data){
        return base64_encode(serialize($data));
    }


    /**
     * @param $serializedData | encrypted serialzie string
     * @return mixed
     */
    public static function safeUnserialize($serializedData){
        return unserialize(base64_decode($serializedData));
    }


    /**
     * @param array $parameters
     * @param array $validationRules
     * @param array $filterRules
     * @return array
     * @throws \App\libraries\Exception
     * @throws \Exception
     */
    public static function validateParameters(array $parameters, $validationRules = array(), $filterRules = array()){
        $validator = new Validator();
        $validator->validation_rules($validationRules);
        //$this->validator->validate($parameters, $rules);
        if(!empty($filterRules) && sizeof($filterRules) > 0){
            $validator->filter_rules($filterRules);
        }
        $validated_data = $validator->run($parameters);
        if($validated_data === false) {
            throw new \Exception($validator->get_readable_errors(true));
        }
        return $validated_data;
    }


    /**
     * Remove array fields/key if they exist within $filterFields. Clean the passed $data based on the
     * $filterFields.
     *
     * @param $filterFields
     * @param array $data
     * @return array
     */
    public static function filterOutputData($filterFields, array $data){
        foreach($data as $key => $record){
            if(in_array($key, $filterFields, true) === true){
                unset($data[$key]);
            }else{
                if(is_array($record)) {
                    foreach ($record as $recordKey => $value) {
                        if(in_array($recordKey, $filterFields, true) == true){
                            unset($data[$key][$recordKey]);
                        }
                    }
                }
            }
        }
        return $data;
    }


    /**
     * @param $exception
     * @param string $type
     * @return bool
     */
    public static function logException($exception, $type = 'Uncaught'){
        $logData =  array(
            'time' => date('F j, Y, g:i a'),
            'level' => $type.' Exception',
            'description' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage() . ' in [' . $exception->getFile() . ', line ' .  $exception->getLine() . ']'
        );
        $fh = fopen(APP_ERROR_LOG_FILE, 'a+');
        if (is_array($logData)) {
            $logData = print_r($logData, 1);
        }
        $status = fwrite($fh, $logData);
        fclose($fh);
        return ($status) ? true : false;
    }

}