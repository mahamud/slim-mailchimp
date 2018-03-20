<?php
/**
 * Overrides defined here
 */

$container = $application->getContainer();


//Override the default 'Not Found Handler'
$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        $output = array(
            'status' => 'Error',
            'message' => NOT_FOUND_MESSAGE
        );
        return $container['response']
            ->withStatus(HTTP_STATUS_NOT_FOUND)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($output));
    };
};

//Override the default 'Not Allowed Handler'
$container['notAllowedHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        $output = array(
            'status' => 'Error',
            'message' => NOT_ALLOWED_MESSAGE
        );
        return $container['response']
            ->withStatus(HTTP_STATUS_NOT_ALLOWED)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($output));
    };
};


//Custom Error Handler
$container['errorHandler'] = function ($container) {
    return function ($request, $response, $exception) use ($container) {
        $response->getBody()->rewind();
        \App\helper\Functions::logException($exception); //Logging of the exception
        $output = array(
            'status' => 'Error',
            'message' => APPLICATION_ERROR_MESSAGE
        );
        return $container['response']
            ->withStatus(HTTP_STATUS_INTERNAL_SERVER_ERROR)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($output));
    };
};


//Custom PHP RunTime Error Handler - Applicable only for PHP 7.x
$container['phpErrorHandler'] = function ($container) {
    return $container['errorHandler'];
};

//Adding database instance to container
$container['database'] = function ($container) { //Container for core database
    /* */
};


//Adding queue
$container['queue'] = function ($container) { //Container for core queue
    /* */
};


//Adding logger to container
$container['logger'] = function() {
    return new \App\middleware\LoggerMiddleWare();
};


//Session Helper
$container['session'] = function () {
    return new \App\libraries\session\Helper();
};


//Adding Controllers to Containers from here onwards
$container['AuthController'] = function ($container) {
    return new \App\controllers\AuthController($container);
};

$container['MailController'] = function ($container) {
    return new \App\controllers\MailController($container);
};

