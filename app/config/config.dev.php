<?php
/**
 * All system configurations, environment specific variables to be defined here.
 *
 */

// Configure Error Reporting
error_reporting(E_ALL);

// Configure Session properties - php-ini settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.hash_function', 'sha512');

session_cache_limiter(false);


// Configure Database Settings IF required
$settings = array();

//Environment Specific Constants
define('API_HOST', 'api.loyalty.com');
define('TOKEN_EXPIRY_LIMIT', 240); //Time in minutes
define('IP_LIMIT', 5000);
define('IP_LIMIT_TIME', 180); //The time is in minutes

//Keys
define('DATA_ENCRYPTION_KEY', getenv('ENCRYPTION_KEY')); //** 44 Characters REQUIRED */
define('MAIL_CHIMP_API_KEY', getenv('MAILCHIMP_API_KEY')); //Mail Chimp API Key