<?php

namespace App\middleware;

use App\helper\Encryption;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use App\helper\Functions;

/**
 * Class AuthMiddleware
 * @package App\middleware
 */
class AuthMiddleware extends Middleware{


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
	public function __invoke(Request $request, Response $response, $next){
        if($this->isRequestMethodValid($request) === false){
            return Functions::generateResponse(array('message' => INVALID_METHOD_MESSAGE), $response, HTTP_STATUS_NOT_ALLOWED);
        }

        //Validate Scheme | Additional validation
        if($request->getUri()->getScheme() !== 'https'){
            return Functions::generateResponse(array('message' => FORBIDDEN_MESSAGE), $response, HTTP_STATUS_FORBIDDEN);
        }

        $callable = $request->getAttributes()['route']->getCallable();
        //Special Condition, only login method will bypass
        if($callable !== LOGIN_CALLABLE_METHOD){
            try {
                $this->validateAuthToken($request);
            }
            catch(\Exception $exception){
                $output = Functions::output(null, API_RESPONSE_FAILED, $exception->getMessage());
                return Functions::generateResponse($output, $response, HTTP_STATUS_UNAUTHORIZED);
            }
        }

        $response = $next($request, $response);
        return $response;
	}


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @throws \Exception
     */
	private function validateAuthToken(Request $request){
        //Validate Scheme | Additional validation
        if($request->getUri()->getScheme() !== 'https'){
            throw new \Exception(AUTH_ERROR_MESSAGE);
        }
        $token = $request->getHeaderLine(HEADER_TOKEN_KEY);
        $encrypt = new Encryption(DATA_ENCRYPTION_KEY);
        $decryptedToken = $encrypt->decrypt($token);
        $tokenParts = explode('@', $decryptedToken);

        //Token Data Structure
        /*
        * [0] = request IP
        * [1] = user agent
        * [2] = timestamp
        * [3] = hostname
        * [4] = user id
        * [5] = username
        */
        if(sizeof($tokenParts) < 5){
            throw new \Exception(AUTH_ERROR_MESSAGE);
        }

        $headers = $request->getHeaders(); //Now, get the current request headers
        //Validate Time limit of token
        if(!empty($tokenParts[3])){
            $difference = time() - (int)$tokenParts[2];
            $minutes = $difference / 60;
            if($minutes >= TOKEN_EXPIRY_LIMIT){
                throw new \Exception(AUTH_TOKEN_EXPIRED_MESSAGE);
            }
        }else{
            throw new \Exception(AUTH_ERROR_MESSAGE);
        }

        //Validate User Agent
        if($tokenParts[1] != $headers['HTTP_USER_AGENT'][0]){
            throw new \Exception(AUTH_ERROR_MESSAGE);
        }
        //Validate hostname
        if($tokenParts[3] != $headers['Host'][0]){
            throw new \Exception(AUTH_ERROR_MESSAGE);
        }

        //if successful login,log account ID in sessions
        if(empty($tokenParts[2]) || empty($tokenParts[5])){
            throw new \Exception(AUTH_ERROR_MESSAGE);
        }
    }
}