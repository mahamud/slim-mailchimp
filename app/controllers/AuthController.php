<?php

namespace App\controllers;

use App\helper\Functions;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use App\models\UserModel;

/**
 * Class AuthController
 * @package app\controllers
 */
class AuthController extends BaseController
{

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function login(Request $request, Response $response){
         if(!empty($request->getParsedBody())) {
            $parameters = filter_var_array($request->getParsedBody(), FILTER_SANITIZE_STRING);
            if (empty($parameters['username']) || empty($parameters['password'])) {
                $output = Functions::output(null, API_RESPONSE_FAILED, REQUIRED_MISSING_MESSAGE);
                return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
            }
            //Now, inititate the login procedure
            $userModel = new UserModel($this->container);
            try {
                $user = $userModel->login($parameters);
                $headers = $request->getHeaders();
                $expiryTime = strtotime('+'.TOKEN_EXPIRY_LIMIT.' minutes', time());
                $token = Functions::generateAuthenticationToken($headers, $user);
                $output = Functions::output(array('authtoken' => $token, 'token_expires' => $expiryTime), API_RESPONSE_SUCCESS, AUTH_SUCCESS_MESSAGE);
                return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
             }
            catch(\Exception $exception){
                $output = Functions::output(null, API_RESPONSE_FAILED, $exception->getMessage());
                return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
            }
        }
        $output = Functions::output(null, API_RESPONSE_FAILED, REQUIRED_MISSING_MESSAGE);
        return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
    }

}
