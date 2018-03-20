<?php

namespace App\middleware;

use App\helper\Functions;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Class CorsMiddleware
 * @package App\middleware
 */
class CorsMiddleware extends Middleware{

    /**
     * CorsMiddleware constructor.
     * @param $container
     */
    public function __construct($container){
        parent::__construct($container);
    }


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
	public function __invoke(Request $request, Response $response, $next){
        $route = $request->getAttribute('route');
        $methods = array();
        if(!empty($route)) {
            $methods = $route->getMethods();
        }
        if($request->getMethod() === HTTP_REQUEST_OPTIONS){
            return Functions::generateOptionsResponse($response,$methods);
        }
        $response = $next($request, $response);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Auth-Token, Content-Type, Accept, Origin')
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $methods));
	}
}