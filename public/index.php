<?php

/**
 * The public facing index page basically redirects traffic to the 'app' folder. The 'app' folder is structured
 * with all the routes, controllers, models and exceptions.
 */

define('BASE_PATH', dirname(__DIR__));
require_once(BASE_PATH . '/app/app.php');


