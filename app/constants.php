<?php
//Data Path
define('LAZER_DATA_PATH', BASE_PATH.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR); //Path to folder with tables

// HTTP Response Codes
define('HTTP_STATUS_OK', 200);
define('HTTP_STATUS_BAD_REQUEST', 400);
define('HTTP_STATUS_UNAUTHORIZED', 401);
define('HTTP_STATUS_TOO_MANY_REQUESTS', 429);
define('HTTP_STATUS_FORBIDDEN', 403);
define('HTTP_STATUS_NOT_FOUND', 404);
define('HTTP_STATUS_NOT_ALLOWED', 405);
define('HTTP_STATUS_CONFLICT', 409);
define('HTTP_STATUS_INTERNAL_SERVER_ERROR', 500);
define('HTTP_BACKEND_ERROR', 503);
define('API_RESPONSE_SUCCESS', 'Successful');
define('API_RESPONSE_FAILED', 'Error');
define('HTTP_REQUEST_OPTIONS', 'OPTIONS');
define('HTTP_REQUEST_GET', 'GET');
define('HTTP_REQUEST_POST', 'POST');
define('HTTP_REQUEST_DELETE', 'DELETE');
define('HTTP_REQUEST_PUT', 'PUT');

// Keys
define('HEADER_TOKEN_KEY', 'X-Auth-Token');


//Misc
define('DEVELOPMENT_ENVIRONMENT', 'dev');
define('DEFAULT_TIME_ZONE', 'Australia/Melbourne');
define('DEFAULT_DB_LIMIT', 50);


//File Paths
define('APP_ERROR_LOG_FILE', BASE_PATH.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'error_log_'.date('Y-m-d').'.log');