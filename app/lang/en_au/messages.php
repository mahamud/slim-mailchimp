<?php if ( ! defined('BASE_PATH')) exit('No direct script access allowed');


// Global Error Messages

define('INVALID_METHOD_MESSAGE', 'Invalid request. Request methods do not match.');
define('FORBIDDEN_MESSAGE', 'The requested operation is forbidden and cannot be completed.');
define('INVALID_HEADER_MESSAGE', 'The request failed because it contained an invalid header.');
define('BAD_REQUEST_MESSAGE', '{"message":"The API request is invalid or improperly formed."}');
define('NOT_FOUND_MESSAGE', 'The requested operation failed because a resource associated with the request could not be found.');
define('NOT_ALLOWED_MESSAGE', 'The HTTP method associated with the request is not supported.');
define('APPLICATION_ERROR_MESSAGE', 'Internal application error.');
define('RUNTIME_SYSTEM_ERROR_MESSAGE', 'Internal system error.');
define('AUTH_ERROR_MESSAGE', 'The authorization credentials provided for the request are invalid. Check the value of the Authorization HTTP request header.');
define('AUTH_SUCCESS_MESSAGE', 'Authentication successful');
define('AUTH_TOKEN_EXPIRED_MESSAGE', 'Token Expired or Invalid. Check the value of the Authorization HTTP request header.');
define('AUTH_TOKEN_INVALID_MESSAGE', 'Invalid token provided. Check the value of the Authorization HTTP request header.');
define('RATE_LIMIT_MESSAGE', 'Too many requests have been sent within a given time span.');
define('BACKEND_ERROR_MESSAGE', 'Unknown application error.');
define('ERRORLOG_ERROR_MESSAGE', 'Failed to log error. Please check folder permissions.');

//Operational Error Messages
define('REQUIRED_MISSING_MESSAGE', 'The API request is missing required information. The required information could be a parameter or resource property.');
define('EMPTY_POST_PUT_PARAMETERS_MESSAGE', 'The API request consists of an empty message body. Review the API documentation to determine which parameters are valid for your request.');
define('NO_PARAMETERS_PASSED_MESSAGE', 'No parameters have been passed.');
define('LOGIN_INCORRECT_ERROR_MSG', 'Incorrect password or username. Please try again.');
define('RECORD_NOT_FOUND', 'No records found.');
define('RECORD_NOT_FOUND_ACCOUNT_ADMIN', 'No account representative found for this account.');
define('INCORRECT_PARAMETERS_ERROR', 'Database error. Incorrect input parameters.');
define('INVALID_PARAMETER_ERROR', 'The request failed because it contained an invalid parameter or parameter value. Review the API documentation to determine which parameters are valid for your request.');
define('RECORD_EXISTS_ERROR', 'Record already exists.');
define('SUCCESS_ON_RECORD_DELETE', 'Record deleted successfully.');
define('ERROR_ON_RECORD_DELETE', 'Error while deleting the record. Please check if the record exists.');
define('SUCCESS_ON_RECORD_UPDATE', 'Record updated successfully.');
define('SUCCESS_ON_RECORD_INSERT', 'Record inserted successfully.');
define('SUCCESS_ON_MEMBER_LINK', 'Member linked to list successfully.');
define('SUCCESS_ON_MEMBER_UNLINK', 'Member removed from list successfully.');
define('SUCCESS_ON_RECORD_CREATE', 'Record created successfully.');
define('OFFSET_ERROR_MESSAGE', 'Offset out of bounds. Please provide an appropriate value.');
define('DB_LIMIT_ERROR', 'Database offset and limit parameters incorrect.');
define('DUPLICATE_REQUEST_ERROR_MESSAGE', 'The API request cannot be completed because the requested operation would conflict with an existing item.');


define('UNEXPECTED_ERROR', 'Unexpected error.');
define('UNEXPECTED_DB_ERROR', 'Unexpected error while processing database request.');
define('DB_LIBRARY_MISSING', 'Database library not found. Run composer update.');
define('APP_LIBRARY_MISSING', 'Application library not found. Please try running composer update.');

//Logger Types
define('LOGGER_TYPE_ERROR', 'error');

// Sepcial Constants
define('LOGIN_CALLABLE_METHOD', 'AuthController:login');

