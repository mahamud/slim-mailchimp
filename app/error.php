<?php if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * Used for logging all php notices,warings and etc in a file when error reporting
 * is set and display_errors is off
 * @uses used in prod env for logging all type of error of php code in a file for further debugging
 * and code performance
 * @author Aditya Mehrotra<aditycse@gmail.com>
 * @modified Mahamud Shahjahan
 */

$errorLogFilePath = BASE_PATH.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'error_log_'.date('Y-m-d').'.log';
$warningLogFilePath = BASE_PATH.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'warning_log_'.date('Y-m-d').'.log';
define('ERROR_LOG_FOLDER', BASE_PATH.DIRECTORY_SEPARATOR.'logs');
define('ERROR_LOG_FILE', $errorLogFilePath);
define('WARNING_LOG_FILE', $warningLogFilePath);

/**
 * @param $code
 * @param $description
 * @param null $file
 * @param null $line
 * @param null $context
 * @return bool
 * @throws ErrorException
 * @throws Exception
 */
function handleError($code, $description, $file = null, $line = null, $context = null) {
    /*$displayErrors = ini_get('display_errors');
    $displayErrors = strtolower($displayErrors);
    if (error_reporting() === 0 || $displayErrors === 'on') {
        return false;
    }*/
    list($error, $log) = mapErrorCode($code);
    $data = array(
        'time' => date('F j, Y, g:i a'),
        'level' => $log,
        'code' => $code,
        'error' => $error,
        'description' => $description,
        'file' => $file,
        'line' => $line,
        'context' => '',//$context,
        'path' => $file,
        'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
    );

    if($data['error'] === 'Fatal Error'){
        fileLog($data);
        throw new \ErrorException('Error ocurred while processing request.', 0, $code, $file, $line);
    }
    return fileLog($data);
}


/**
 * @param $logData
 * @param string $fileName
 * @return bool
 * @throws Exception
 */
function fileLog($logData, $fileName = WARNING_LOG_FILE) {
    if(!file_exists(ERROR_LOG_FOLDER) ) {
        stopAndReturnResponse(ERRORLOG_ERROR_MESSAGE);
    }

    if(!empty($logData['error']) && $logData['error'] === 'Fatal Error'){
        $fileName = ERROR_LOG_FILE;
    }

    $fh = fopen($fileName, 'a+');
    if (is_array($logData)) {
        $logData = print_r($logData, 1);
    }
    if(!is_writable($fileName)) { // Test if the file is writable
        stopAndReturnResponse(ERRORLOG_ERROR_MESSAGE);
    }
    $status = fwrite($fh, $logData);
    fclose($fh);
    return ($status) ? true : false;
}


/**
 * Map an error code into an Error word, and log location.
 *
 * @param int $code Error code to map
 * @return array Array of error word, and log location.
 */
function mapErrorCode($code) {
    $error = $log = null;
    switch ($code) {
        case E_PARSE:
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            $log = LOG_ERR;
            break;
        case E_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_RECOVERABLE_ERROR:
            $error = 'Warning';
            $log = LOG_WARNING;
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            $log = LOG_NOTICE;
            break;
        case E_STRICT:
            $error = 'Strict';
            $log = LOG_NOTICE;
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $error = 'Deprecated';
            $log = LOG_NOTICE;
            break;
        default :
            break;
    }
    return array($error, $log);
}


//Calling custom Error Handler
set_error_handler('handleError');

function exception_handler($exception) {
    $logData =  array(
        'time' => date('F j, Y, g:i a'),
        'level' => 'Uncaught Exception',
        'description' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'message' => 'Fatal Error: ' . $exception->getMessage() . ' in [' . $exception->getFile() . ', line ' .  $exception->getLine() . ']'
    );
    fileLog($logData);
    stopAndReturnResponse(BACKEND_ERROR_MESSAGE, false);
}


/**
 * Function to stop further processing (if required) and return message
 *
 * @param $message
 * @param bool $exit
 */
function stopAndReturnResponse($message, $exit = true){
    ob_end_clean(); # try to purge content sent so far
    header('HTTP/1.1 '.HTTP_BACKEND_ERROR.' 	'.$message);
    header('Content-type: application/json');
    echo json_encode(array('status' => 'Error', 'message' => $message));
    if($exit) {
        exit();
    }
}


//Calling Custon Uncaught Exception Handler
set_exception_handler('exception_handler');
