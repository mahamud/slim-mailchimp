<?php

namespace App\middleware;

use App\helper\Functions;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/**
 * Class APIRateLimitMiddleware
 *
 * @author Mahamud Shahjahan
 * @package App\middleware
 */
class APIRateLimitMiddleware extends Middleware
{

    private $requests;
    private $inmins;
    private $originip;
    private $blacklisted;


    /**
     * APIRateLimitMiddleware constructor.
     * @param $requests
     * @param $inmins
     * @param $database
     */
    public function __construct($requests, $inmins, $database) {
        $this->requests = $requests;
        $this->inmins = $inmins;
        $this->init();
    }


    /**
     *
     */
    protected function init(){
        $this->blacklisted = array();
    }


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next){
        $this->originip = Functions::getIPAddress();//$this->infoAboutIP()['REMOTE_ADDR'];
        if(in_array($this->originip, $this->blacklisted) == true){
            $output = Functions::output(null, API_RESPONSE_FAILED, FORBIDDEN_MESSAGE);
            return Functions::generateResponse($output, $response, HTTP_STATUS_FORBIDDEN);
        }
        if ($this->mustBeThrottled() === TRUE) {
            $output = Functions::output(null, API_RESPONSE_FAILED, RATE_LIMIT_MESSAGE);
            return Functions::generateResponse($output, $response, HTTP_STATUS_TOO_MANY_REQUESTS);
        }
        $response = $next($request, $response);
        return $response;
    }


    /**
     * @return bool
     */
    protected function mustBeThrottled(){

        //todo Query model to get requests per IP
        $requests = 0; //Sample declaration

        if ($requests >= $this->requests) {
            return TRUE;
        }
        //Insert a New Record
        try {
            //todo query model and insert/update record in DB
        }
        catch (\Exception $exception) {
            Functions::logException($exception);
            return FALSE;
        }
        return FALSE;
    }



    /**
     * @param $txt
     * @return int
     */
    protected function ipVersion($txt){
        return strpos($txt, ":") === false ? 4 : 6;
    }

}