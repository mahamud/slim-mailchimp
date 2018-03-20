<?php

namespace App\middleware;
use App\helper\Functions;


/**
 * Class Logger
 * @package App\middleware
 */

class LoggerMiddleWare {

    const EMERGENCY = 1;
    const ALERT     = 2;
    const CRITICAL  = 3;
    const ERROR     = 4;
    const WARN      = 5;
    const NOTICE    = 6;
    const INFO      = 7;
    const DEBUG     = 8;
    const VERSION   = "0.1.0";

    /**
     * @var resource
     */
    protected $resource;
    /**
     * @var array
     */
    protected $settings;
    /**
     * Logger constructor.
     *
     * Preparing Logger. Available settings are:
     *
     * path:
     * (string) The relative or absolute filesystem path to a writable directory.
     *
     * name:
     * (string) The log file name (Prefix file name).
     *
     * name_format:
     * (string) The log file name format; parsed with `date()`.
     *
     * extension:
     * (string) The file extention to append to the filename`.
     *
     * message_format:
     * (string) The log message format; available tokens are...
     *     %label%      Replaced with the log message level (e.g. FATAL, ERROR, WARN).
     *     %date%       Replaced with a ISO8601 date string for current timezone.
     *     %message%    Replaced with the log message, coerced to a string.
     *
     * @param array $settings Settings
     *
     */
    public function __construct($settings = array()){
        // Merge settings
        $this->settings =  array_merge(array(
            'path' => BASE_PATH.'/logs/',
            'name' => 'api_logger_',
            'name_format' => 'Y-m-d',
            'extension' => 'log',
            'message_format' => '[%label%] %date% %message%'
        ), $settings);
        // Remove trailing slash from log path
        $this->settings['path'] = rtrim($this->settings['path'], DIRECTORY_SEPARATOR);
    }


    /**
     * Logger Middleware for Slim framework
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next){
        // Begin of time
        //$start = microtime();
        // URL accessed
        $path = $request->getUri()->getPath();
        // Call next middleware
        $response = $next($request, $response);
        // End of time
        //$end = microtime();
        // Latency
        //$latency = $end - $start;
        // Client IP address
        $clientIP = $this->getIpAddress();
        // Method access
        $method = $request->getMethod();
        //$this->write(sprintf("|%d|%f s|%s|%s %s", $response->getStatusCode(), $latency, $clientIP, $method, $path), self::INFO);
        $this->write(('Response Status Code:'.$response->getStatusCode().' ** Client IP:'.$clientIP.' ** Requested API Method:'.$method.' ** Path:'.$path),
            self::INFO);
        return $response;
    }


    /**
     * Write to log
     *
     * @param mixed $object Object
     * @param int   $level  Level
     *
     * @return void
     */
    public function write($object, $level){
        // Determine label
        $label = $this->getLoggerLabel($level);

        // Get formatted log message
        $message = str_replace(
            array("%label%", "%date%", "%message%"),
            array($label, date("c"), (string)$object),
            $this->settings['message_format']
        );
        if ( ! $this->resource) {
            $filename = $this->settings['name'];
            $filename .= date($this->settings['name_format']);
            if (! empty($this->settings['extension'])) {
                $filename .= '.' . $this->settings['extension'];
            }
            $this->resource = fopen($this->settings['path'] . DIRECTORY_SEPARATOR . $filename, 'a');
        }
        // Output to resource
        fwrite($this->resource, $message . PHP_EOL);
    }


    /**
     * @param $data
     * @param $level
     * @param null $message
     */
    public function generateResponseLog($data, $level, $message = null){
        if(empty($message)) {
            $this->write(('Response Data: ' . print_r($data, true)), $this->getLoggerLabel($level));
        }else{
            $this->write(('Response Data: [' . $message . ']' . print_r($data, true)), $this->getLoggerLabel($level));
        }
    }


    /**
     * @param $data
     * @param $level
     * @param null $message
     */
    public function generateRequestLog($data, $level, $message = null){
        if(!empty($message)) {
            $this->write(('Request Data: [' . $message . ']' . print_r($data, true)), $this->getLoggerLabel($level));
        }else{
            $this->write(('Request Data: ' . print_r($data, true)), $this->getLoggerLabel($level));
        }
    }


    /**
     * @param $content
     * @param $level
     */
    public function generateGenericLog($content, $level){
        $this->write($content, $this->getLoggerLabel($level));
    }


    /**
     * @param $level
     * @return string
     */
    private function getLoggerLabel($level){
        $label = "DEBUG";
        switch($level) {
            case self::CRITICAL:
            case 'CRITICAL':
                $label = 'CRITICAL';
                break;
            case self::ERROR:
            case 'ERROR':
                $label = 'ERROR';
                break;
            case self::WARN:
                case 'WARN':
                $label = 'WARN';
                break;
            case self::INFO:
            case 'INFO':
                $label = 'INFO';
                break;
        }
        return $label;
    }


    /**
     * @param $constant
     * @return int
     */
    public function getConstantValue($constant){
        switch($constant){
            case 'DEBUG':
                $value = self::DEBUG;
                break;
            case 'ERROR':
                $value = self::ERROR;
                break;
            default:
                $value = self::INFO;
                break;
        }
        return $value;
    }



    /**
     * Write to log with debug level
     *
     * @param mixed $object Object
     *
     * @return void
     */
    public function debug($object){
        $this->write($object, self::DEBUG);
    }


    /**
     * Write to log with critical level
     *
     * @param $object
     *
     * @return void
     */
    public function critical($object){
        $this->write($object, self::CRITICAL);
    }


    /**
     * Write to log with error level
     *
     * @param $object
     *
     * @return void
     */
    public function error($object){
        $this->write($object, self::ERROR);
    }


    /**
     * @return string
     */
    private function getIpAddress(){
        return Functions::getIPAddress();
    }
}