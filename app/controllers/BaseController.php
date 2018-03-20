<?php

namespace App\controllers;
use App\exception\ConflictException;
use App\helper\Functions;
use App\libraries\session\Session;
use App\middleware\LoggerMiddleWare;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BaseController
 * @property Session $session
 * @property ServerRequestInterface $request
 * @property ResponseInterface $response
 * @property LoggerMiddleWare $logger
 * @property ContainerInterface $container
 * @package App\controllers
 *
 */

class BaseController
{

    /**
     * Core variable declaration
     *
     * @var
     */
    protected $container;
    protected $session;
    protected $response;
    protected $request;
    protected $offset;
    protected $limit;
    protected $logger;
    protected $events;

    /**
     * BaseController constructor.
     * @param $container
     */
    public function __construct($container){
        $this->container = $container;
        $this->session = $this->container->session;
        $this->response = $this->container->response;
        $this->request = $this->container->request;
        $this->logger = $this->container->logger;
        $this->initiateRequestValidation();
    }


    /**
     * @param $property
     * @return mixed
     */
    public function __get($property){
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }
    }


    /**
     * Method to initiate offset and limit
     */
    protected function setOffsetAndLimit(){
        $parameters = $this->request->getQueryParams();
        $this->offset = !empty($parameters['offset']) ? (int)filter_var($parameters['offset'], FILTER_SANITIZE_NUMBER_INT) : 0;
        if((!empty($parameters['limit']) && (int)$parameters['limit'] <= DEFAULT_DB_LIMIT) ){
            $this->limit = (int)filter_var($parameters['limit'], FILTER_SANITIZE_NUMBER_INT);
        }else{
            $this->limit = DEFAULT_DB_LIMIT;
        }
    }


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @throws \Exception
     */
    protected function validateRequestHeaders(Request $request){
        $headers = $request->getHeaders();
        $headerKeys = array(
            'Host', 'HTTP_CONNECTION', 'HTTP_CACHE_CONTROL', 'CONTENT_TYPE'
        );
        foreach($headerKeys as $key => $value){
            $error = false;
            if(!empty($headers[$value])) {
                switch ($value) {
                    case 'Host':
                        $error = ($headers[$value][0] !== API_HOST) ? true : false;
                        break;
                    case 'HTTP_CONNECTION':
                        $error = (strtolower($headers[$value][0]) !== 'keep-alive') ? true : false;
                        break;
                    case 'HTTP_CACHE_CONTROL':
                        $error = (strtolower($headers[$value][0]) !== 'no-cache') ? true : false;
                        break;
                    case 'CONTENT_TYPE':
                        $error = (stripos($headers[$value][0],'application/json') === false);
                        break;
                }
            }
            if($error){
                throw new \Exception(INVALID_HEADER_MESSAGE);
            }
        }
    }


    /**
     * @param $method
     * @param $data
     * @throws ConflictException
     */
    protected function validateRequest($method, $data){
        $sessionKey = $this->session->id().'_'.$method;
        $exists = $this->session->exists($sessionKey);
        if($exists){
            $sessionData = $this->session->get($sessionKey);
            if($sessionData === Functions::safeSerialize($data)){
                throw new ConflictException(DUPLICATE_REQUEST_ERROR_MESSAGE);
            }
        }
        $this->session->set($sessionKey, Functions::safeSerialize($data));
    }


    /**
     *
     */
    protected function initiateRequestValidation(){
        try{
            $this->validateRequestHeaders($this->request);
        }
        catch(\Exception $exception){
            http_response_code(HTTP_STATUS_BAD_REQUEST);
            echo json_encode(array('status' => API_RESPONSE_FAILED, 'message' => $exception->getMessage()));
            die();
        }
    }

}