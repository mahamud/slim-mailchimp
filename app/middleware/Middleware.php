<?php

namespace App\middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class Middleware
 * @package App\Middleware
 */
class Middleware{

    protected $container;

    /**
     * Middleware constructor.
     * @param $container
     */
    public function __construct($container){
        $this->container = $container;
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @return bool
     */
    protected function isRequestMethodValid(Request $request){
        if($request->getOriginalMethod() != $request->getMethod()){
            return false;
        }
        return true;
    }

}