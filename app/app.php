<?php if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

// Autoload
require_once BASE_PATH . '/vendor/autoload.php';

//Custom Error Handling
require_once BASE_PATH . '/app/error.php';

// Get Language File
require_once BASE_PATH . '/app/lang/en_au/messages.php';

//Get the Constants
require_once BASE_PATH . '/app/constants.php';

// Get Config File as per environment
require_once BASE_PATH . '/app/config/config.'.getenv('ENVIRONMENT').'.php';

//Set default Date/Time Zone
date_default_timezone_set(DEFAULT_TIME_ZONE);

// Configure Slim
if (!class_exists('\Slim\App')) {
    throw new \Exception(APP_LIBRARY_MISSING);
}
$application = new \Slim\App($settings);


// Set up Dependencies
require_once BASE_PATH . '/app/dependencies.php';


//Initiate the session
//todo: Wanted to implement the session on the temporary json DB, unfortunately didn't get time
/*$application->add(new \App\libraries\session\Session([
    'autorefresh' => true,
    'httponly'    => true,
    'lifetime' => '3 hour'
]));*/

// Configure Routes
require_once BASE_PATH . '/app/routes.php';

//Middleware for CORS (Cross Origin Resource Sharing)
$application->add(new \App\middleware\CorsMiddleware($container));

//Add Logger Middleware
$application->add(new \App\middleware\LoggerMiddleWare());

//MiddleWare for API Rate Limit
//todo Middleware for API rate limitimg to be added here

// Run the Application
$application->run();
