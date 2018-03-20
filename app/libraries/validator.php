<?php

namespace App\libraries;

use App\helper\Lookup;

/**
 * PHP input validation class
 *
 * Class Validator
 *
 * @modified Mahamud Shahjahan
 *
 */
class Validator
{
    // Singleton instance of GUMP
    protected static $instance = null;

    // Validation rules for execution
    protected $validation_rules = array();

    // Filter rules for execution
    protected $filter_rules = array();

    // Instance attribute containing errors from last run
    protected $errors = array();

    // Contain readable field names that have been set manually
    protected static $fields = array();

    // Custom validation methods
    protected static $validation_methods = array();

    // Custom validation methods error messages and custom ones
    protected static $validation_methods_errors = array();

    // Customer filter methods
    protected static $filter_methods = array();


    /**
     * @return null|static
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }


    // ** ------------------------- Validation Data ------------------------------- ** //

    public static $basic_tags = '<br><p><a><strong><b><i><em><img><blockquote><code><dd><dl><hr><h1><h2><h3><h4><h5><h6><label><ul><li><span><sub><sup>';

    public static $en_noise_words = "about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,
                                     being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,
                                     got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,its,it's,like,
                                     make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,
                                     our,out,over,said,same,see,should,since,some,still,such,take,than,that,
                                     the,their,them,then,there,these,they,this,those,through,to,too,under,up,
                                     very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,
                                     b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_";

    // field characters below will be replaced with a space.
    protected $fieldCharsToRemove = array('_', '-');

    protected $lang;


    // ** ------------------------- Validation Helpers ---------------------------- ** //

    public function __construct($lang = 'en'){
        //Do nothjing at this stage
        /*if ($lang) {
            $lang_file = __DIR__ . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php';

            if (file_exists($lang_file)) {
                $this->lang = $lang;
            } else {
                throw new \Exception('Language with key "' . $lang . '" does not exist');
            }
        }*/
    }

    /**
     * Shorthand method for inline validation.
     *
     * @param array $data The data to be validated
     * @param array $validators The validators
     *
     * @return mixed True(boolean) or the array of error messages
     */
    public static function is_valid(array $data, array $validators)
    {
        $gump = self::get_instance();

        $gump->validation_rules($validators);

        if ($gump->run($data) === false) {
            return $gump->get_readable_errors(false);
        } else {
            return true;
        }
    }

    /**
     * Shorthand method for running only the data filters.
     *
     * @param array $data
     * @param array $filters
     *
     * @return mixed
     */
    public static function filter_input(array $data, array $filters)
    {
        $gump = self::get_instance();

        return $gump->filter($data, $filters);
    }

    /**
     * Magic method to generate the validation error messages.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get_readable_errors(true);
    }

    /**
     * Perform XSS clean to prevent cross site scripting.
     *
     * @static
     *
     * @param array $data
     *
     * @return array
     */
    public static function xss_clean(array $data)
    {
        foreach ($data as $k => $v) {
            $data[$k] = filter_var($v, FILTER_SANITIZE_STRING);
        }

        return $data;
    }

    /**
     * Adds a custom validation rule using a callback function.
     *
     * @param string $rule
     * @param callable $callback
     * @param string $error_message
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function add_validator($rule, $callback, $error_message = null)
    {
        $method = 'validate_' . $rule;

        if (method_exists(__CLASS__, $method) || isset(self::$validation_methods[$rule])) {
            throw new Exception("Validator rule '$rule' already exists.");
        }

        self::$validation_methods[$rule] = $callback;
        if ($error_message) {
            self::$validation_methods_errors[$rule] = $error_message;
        }

        return true;
    }

    /**
     * Adds a custom filter using a callback function.
     *
     * @param string $rule
     * @param callable $callback
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function add_filter($rule, $callback)
    {
        $method = 'filter_' . $rule;

        if (method_exists(__CLASS__, $method) || isset(self::$filter_methods[$rule])) {
            throw new Exception("Filter rule '$rule' already exists.");
        }

        self::$filter_methods[$rule] = $callback;

        return true;
    }

    /**
     * Helper method to extract an element from an array safely
     *
     * @param mixed $key
     * @param array $array
     * @param mixed $default
     * @return mixed
     */
    public static function field($key, array $array, $default = null)
    {
        if (!is_array($array)) {
            return null;
        }

        if (isset($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }

    /**
     * Getter/Setter for the validation rules.
     *
     * @param array $rules
     *
     * @return array
     */
    public function validation_rules(array $rules = array())
    {
        if (empty($rules)) {
            return $this->validation_rules;
        }

        $this->validation_rules = $rules;
    }

    /**
     * Getter/Setter for the filter rules.
     *
     * @param array $rules
     *
     * @return array
     */
    public function filter_rules(array $rules = array())
    {
        if (empty($rules)) {
            return $this->filter_rules;
        }

        $this->filter_rules = $rules;
    }

    /**
     * Run the filtering and validation after each other.
     *
     * @param array $data
     * @param bool $check_fields
     *
     * @return array
     *
     * @throws Exception
     */
    public function run(array $data, $check_fields = false)
    {
        $data = $this->filter($data, $this->filter_rules());

        $validated = $this->validate(
            $data, $this->validation_rules()
        );

        if ($check_fields === true) {
            $this->check_fields($data);
        }

        if ($validated !== true) {
            return false;
        }

        return $data;
    }

    /**
     * Ensure that the field counts match the validation rule counts.
     *
     * @param array $data
     */
    private function check_fields(array $data)
    {
        $ruleset = $this->validation_rules();
        $mismatch = array_diff_key($data, $ruleset);
        $fields = array_keys($mismatch);

        foreach ($fields as $field) {
            $this->errors[] = array(
                'field' => $field,
                'value' => $data[$field],
                'rule' => 'mismatch',
                'param' => null,
            );
        }
    }

    /**
     * Sanitize the input data.
     *
     * @param array $input
     * @param array $fields
     * @param bool $utf8_encode
     *
     * @return array
     */
    public function sanitize(array $input, array $fields = array(), $utf8_encode = true)
    {
        $magic_quotes = (bool)get_magic_quotes_gpc();

        if (empty($fields)) {
            $fields = array_keys($input);
        }

        $return = array();

        foreach ($fields as $field) {
            if (!isset($input[$field])) {
                continue;
            } else {
                $value = $input[$field];
                if (is_array($value)) {
                    $value = $this->sanitize($value);
                }
                if (is_string($value)) {
                    if ($magic_quotes === true) {
                        $value = stripslashes($value);
                    }

                    if (strpos($value, "\r") !== false) {
                        $value = trim($value);
                    }

                    if (function_exists('iconv') && function_exists('mb_detect_encoding') && $utf8_encode) {
                        $current_encoding = mb_detect_encoding($value);

                        if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                            $value = iconv($current_encoding, 'UTF-8', $value);
                        }
                    }

                    $value = filter_var($value, FILTER_SANITIZE_STRING);
                }

                $return[$field] = $value;
            }
        }

        return $return;
    }

    /**
     * Return the error array from the last validation run.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param array $input
     * @param array $ruleset
     * @return array|bool
     * @throws \Exception
     */
    public function validate(array $input, array $ruleset)
    {
        $this->errors = array();

        foreach ($ruleset as $field => $rules) {

            $rules = explode('|', $rules);

            $lookFor = array('required_file', 'required');

            // if (in_array('required', $rules) || (isset($input[$field]) && !is_array($input[$field]))) {
            if (count(array_intersect($lookFor, $rules)) > 0 || (isset($input[$field]) && !is_array($input[$field]))) {
                foreach ($rules as $rule) {
                    $method = null;
                    $param = null;

                    // Check if we have rule parameters
                    if (strstr($rule, ',') !== false) {
                        $rule = explode(',', $rule);
                        $method = 'validate_' . $rule[0];
                        $param = $rule[1];
                        $rule = $rule[0];

                        // If there is a reference to a field
                        if (preg_match('/(?:(?:^|;)_([a-z_]+))/', $param, $matches)) {

                            // If provided parameter is a field
                            if (isset($input[$matches[1]])) {
                                $param = str_replace('_' . $matches[1], $input[$matches[1]], $param);
                            }
                        }
                    } else {
                        $method = 'validate_' . $rule;
                    }

                    //self::$validation_methods[$rule] = $callback;

                    if (is_callable(array($this, $method))) {
                        $result = $this->$method(
                            $field, $input, $param
                        );

                        if (is_array($result)) {
                            $this->errors[] = $result;
                        }
                    } elseif (isset(self::$validation_methods[$rule])) {
                        $result = call_user_func(self::$validation_methods[$rule], $field, $input, $param);

                        if ($result === false) {
                            $this->errors[] = array(
                                'field' => $field,
                                'value' => $input,
                                'rule' => $rule,
                                'param' => $param,
                            );
                        }
                    } else {
                        throw new \Exception("Validator method '$method' does not exist.");
                    }
                }
            }
        }

        return (count($this->errors) > 0) ? $this->errors : true;
    }

    /**
     * Overloadable method to invoke validation.
     *
     * @param array $input
     * @param $rules
     * @param $field
     *
     * @return bool
     */
    protected function shouldRunValidation(array $input, $rules, $field)
    {
        return in_array('required', $rules) || (isset($input[$field]) && trim($input[$field]) != '');
    }

    /**
     * Set a readable name for a specified field names.
     *
     * @param string $field
     * @param string $readable_name
     */
    public static function set_field_name($field, $readable_name)
    {
        self::$fields[$field] = $readable_name;
    }

    /**
     * Set readable name for specified fields in an array.
     *
     * Usage:
     *
     * Validator::set_field_names(array(
     *  "name" => "My Lovely Name",
     *  "username" => "My Beloved Username",
     * ));
     *
     * @param array $array
     */
    public static function set_field_names(array $array)
    {
        foreach ($array as $field => $readable_name) {
            self::set_field_name($field, $readable_name);
        }
    }

    /**
     * Set a custom error message for a validation rule.
     *
     * @param string $rule
     * @param string $message
     */
    public static function set_error_message($rule, $message)
    {
        $gump = self::get_instance();
        self::$validation_methods_errors[$rule] = $message;
    }

    /**
     * Set custom error messages for validation rules in an array.
     *
     * Usage:
     *
     * Validator::set_error_messages(array(
     *  "validate_required"     => "{field} is required",
     *  "validate_valid_email"  => "{field} must be a valid email",
     * ));
     *
     * @param array $array
     */
    public static function set_error_messages(array $array)
    {
        foreach ($array as $rule => $message) {
            self::set_error_message($rule, $message);
        }
    }

    /**
     * Get error messages.
     *
     * @return array
     */
    protected function get_messages(){
        $messages = array(
            'validate_required' => 'The {field} field is required.',
            'validate_required_special' => 'Either {field} or {param} is required and cannot be empty.',
            'validate_valid_email' => 'The {field} field must be a valid email address.',
            'validate_max_len' => 'The {field} field needs to be {param} characters or less.',
            'validate_min_len' => 'The {field} field needs to be at least {param} characters.',
            'validate_exact_len' => 'The {field} field needs to be exactly {param} characters.',
            'validate_alpha' => 'The {field} field may only contain letters.',
            'validate_alpha_numeric' => 'The {field} field may only contain letters and numbers.',
            'validate_alpha_numeric_space' => 'The {field} field may only contain letters, numbers, spaces, semi-colon, commas, single quotes, dashes, underscores.',
            'validate_alpha_dash' => 'The {field} field may only contain letters and dashes.',
            'validate_alpha_space' => 'The {field} field may only contain letters and spaces.',
            'validate_numeric' => 'The {field} field must be a number.',
            'validate_integer' => 'The {field} field must be a number without a decimal.',
            'validate_boolean' => 'The {field} field has to be either true or false.',
            'validate_float' => 'The {field} field must be a number with a decimal point (float).',
            'validate_valid_url' => 'The {field} field has to be a URL.',
            'validate_url_exists' => 'The {field} URL does not exist.',
            'validate_valid_ip' => 'The {field} field needs to be a valid IP address.',
            'validate_valid_ipv4' => 'The {field} field needs to contain a valid IPv4 address.',
            'validate_valid_ipv6' => 'The {field} field needs to contain a valid IPv6 address.',
            'validate_guidv4' => 'The {field} field needs to contain a valid GUID.',
            'validate_valid_cc' => 'The {field} is not a valid credit card number.',
            'validate_valid_name' => 'The {field} should be a full name.',
            'validate_contains' => 'The {field} can only be one of the following: {param}.',
            'validate_contains_list' => 'The {field} is not a valid option.',
            'validate_doesnt_contain_list' => 'The {field} field contains a value that is not accepted.',
            'validate_street_address' => 'The {field} field needs to be a valid street address.',
            'validate_valid_date' => 'The {field} must be a valid date.',
            'validate_min_numeric' => 'The {field} field needs to be a numeric value, equal to, or higher than {param}.',
            'validate_max_numeric' => 'The {field} field needs to be a numeric value, equal to, or lower than {param}.',
            'validate_min_age' => 'The {field} field needs to have an age greater than or equal to {param}.',
            'validate_invalid' => 'The {field} field is invalid.',
            'validate_starts' => 'The {field} field needs to start with {param}.',
            'validate_extension' => 'The {field} field can only have one of the following extensions: {param}.',
            'validate_required_file' => 'The {field} field is required.',
            'validate_equalsfield' => 'The {field} field does not equal {param} field.',
            'validate_iban' => 'The {field} field needs to contain a valid IBAN.',
            'validate_phone_number' => 'The {field} field needs to be a valid Phone Number.',
            'validate_regex' => 'The {field} field needs to contain a value with valid format.',
            'validate_valid_json_string' => 'The {field} field needs to contain a valid JSON format string.',
            'validate_valid_array_size_greater' => 'The {field} fields needs to be an array with a size, equal to, or higher than {param}.',
            'validate_valid_array_size_lesser' => 'The {field} fields needs to be an array with a size, equal to, or lower than {param}.',
            'validate_valid_array_size_equal' => 'The {field} fields needs to be an array with a size equal to {param}.',
            'validate_valid_mobile_phone' => 'The {field} is not a valid phone number.'
        );

        if ($validation_methods_errors = self::$validation_methods_errors) {
            $messages = array_merge($messages, $validation_methods_errors);
        }
        return $messages;
    }

    /**
     * @param bool $convert_to_string
     * @return array|null|string
     * @throws \Exception
     */
    public function get_readable_errors($convert_to_string = false)
    {
        if (empty($this->errors)) {
            return ($convert_to_string) ? null : array();
        }

        $resp = array();

        // Error messages
        $messages = $this->get_messages();

        foreach ($this->errors as $e) {
            $field = ucwords(str_replace($this->fieldCharsToRemove, chr(32), $e['field']));
            $param = $e['param'];

            // Let's fetch explicit field names if they exist
            if (array_key_exists($e['field'], self::$fields)) {
                $field = self::$fields[$e['field']];

                // If param is a field (i.e. equalsfield validator)
                if (array_key_exists($param, self::$fields)) {
                    $param = self::$fields[$e['param']];
                }
            }


            // Messages
            if (isset($messages[$e['rule']])) {
                if (is_array($param)) {
                    $param = implode(', ', $param);
                }
                //$message = str_replace('{param}', $param, str_replace('{field}', '<span class="' . $field_class . '">' . $field . '</span>', $messages[$e['rule']]));
                $message = str_replace('{param}', $param, str_replace('{field}', '' . $field . '', $messages[$e['rule']].' '));
                $message = rtrim($message); //Remove white spaces at the end
                $resp[] = $message;
            } else {
                throw new \Exception ('Rule "' . $e['rule'] . '" does not have an error message');
            }
        }

        if (!$convert_to_string) {
            return $resp;
        } else {
            $buffer = '';
            foreach ($resp as $s) {
                //$buffer .= "<span class=\"$error_class\">$s</span>";
                $buffer .= "$s";
            }
            return $buffer;
        }
    }

    /**
     * @param null $convert_to_string
     * @return array|null
     * @throws \Exception
     */
    public function get_errors_array($convert_to_string = null)
    {
        if (empty($this->errors)) {
            return ($convert_to_string) ? null : array();
        }

        $resp = array();

        // Error messages
        $messages = $this->get_messages();

        foreach ($this->errors as $e) {
            $field = ucwords(str_replace(array('_', '-'), chr(32), $e['field']));
            $param = $e['param'];

            // Let's fetch explicit field names if they exist
            if (array_key_exists($e['field'], self::$fields)) {
                $field = self::$fields[$e['field']];

                // If param is a field (i.e. equalsfield validator)
                if (array_key_exists($param, self::$fields)) {
                    $param = self::$fields[$e['param']];
                }
            }

            // Messages
            if (isset($messages[$e['rule']])) {
                // Show first validation error and don't allow to be overwritten
                if (!isset($resp[$e['field']])) {
                    if (is_array($param)) {
                        $param = implode(', ', $param);
                    }
                    $message = str_replace('{param}', $param, str_replace('{field}', $field, $messages[$e['rule']]));
                    $resp[$e['field']] = $message;
                }
            } else {
                throw new \Exception ('Rule "' . $e['rule'] . '" does not have an error message');
            }
        }

        return $resp;
    }

    /**
     * Filter the input data according to the specified filter set.
     *
     * @param mixed $input
     * @param array $filterset
     *
     * @throws Exception
     *
     * @return mixed
     *
     * @throws \Exception
     */

    public function filter(array $input, array $filterset)
    {
        foreach ($filterset as $field => $filters) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $filters = explode('|', $filters);

            foreach ($filters as $filter) {
                $params = null;

                if (strstr($filter, ',') !== false) {
                    $filter = explode(',', $filter);

                    $params = array_slice($filter, 1, count($filter) - 1);

                    $filter = $filter[0];
                }

                if (is_callable(array($this, 'filter_' . $filter))) {
                    $method = 'filter_' . $filter;
                    $input[$field] = $this->$method($input[$field], $params);
                } elseif (function_exists($filter)) {
                    $input[$field] = $filter($input[$field]);
                } elseif (isset(self::$filter_methods[$filter])) {
                    $input[$field] = call_user_func(self::$filter_methods[$filter], $input[$field], $params);
                } else {
                    throw new \Exception("Filter method '$filter' does not exist.");
                }
            }
        }

        return $input;
    }

    // ** ------------------------- Filters --------------------------------------- ** //

    /**
     * Replace noise words in a string (http://tax.cchgroup.com/help/Avoiding_noise_words_in_your_search.htm).
     *
     * Usage: '<index>' => 'noise_words'
     *
     * @param string $value
     *
     * @return string
     */
    protected function filter_noise_words($value){
        $value = preg_replace('/\s\s+/u', chr(32), $value);
        $value = " $value ";
        $words = explode(',', self::$en_noise_words);
        foreach ($words as $word) {
            $word = trim($word);
            $word = " $word "; // Normalize
            if (stripos($value, $word) !== false) {
                $value = str_ireplace($word, chr(32), $value);
            }
        }
        return trim($value);
    }

    /**
     * Remove all known punctuation from a string.
     *
     * Usage: '<index>' => 'rmpunctuataion'
     *
     * @param string $value
     *
     * @return string
     */
    protected function filter_rmpunctuation($value)
    {
        return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value);
    }


    /**
     * Sanitize the string.
     *
     * Usage: '<index>' => 'sanitize_string'
     * @param string $value
     * @param null $params
     * @return string
     */
    protected function filter_sanitize_string($value, $params = null){
        if(!empty($params) && in_array('special_characters', $params)){
            $value = str_replace(array('+'), '', $value);
        }
        return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    /**
     * Sanitize the string by urlencoding characters.
     *
     * Usage: '<index>' => 'urlencode'
     *
     * @param string $value
     *
     * @return string
     */
    protected function filter_urlencode($value)
    {
        return filter_var($value, FILTER_SANITIZE_ENCODED);
    }

    /**
     * Sanitize the string by converting HTML characters to their HTML entities.
     *
     * Usage: '<index>' => 'htmlencode'
     * @param string $value
     *
     * @return string
     */
    protected function filter_htmlencode($value)
    {
        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Sanitize the string by removing illegal characters from emails.
     *
     * Usage: '<index>' => 'sanitize_email'
     *
     * @param string $value
     *
     * @return string
     */
    protected function filter_sanitize_email($value)
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize the string by removing illegal characters from numbers.
     *
     * @param string $value
     *
     * @return string
     */
    protected function filter_sanitize_numbers($value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize the string by removing illegal characters from float numbers.
     *
     * @param string $value
     * @param array $params
     *
     * @return string
     */
    protected function filter_sanitize_floats($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Filter out all HTML tags except the defined basic tags.
     *
     * @param string $value
     * @param array $params
     *
     * @return string
     */
    protected function filter_basic_tags($value, $params = null)
    {
        return strip_tags($value, self::$basic_tags);
    }

    /**
     * Convert the provided numeric value to a whole number.
     *
     * @param string $value
     * @param array $params
     *
     * @return string
     */
    protected function filter_whole_number($value, $params = null)
    {
        return intval($value);
    }

    /**
     * @param $value
     * @param null $params
     * @return string
     * @throws \Exception
     */
    protected function filter_format_date($value, $params = null){
        if(empty($value) || sizeof($params) < 2){
            throw new \Exception('Failed to filter and format date. Value is either empty or format inserted in wrong.');
        }
        $currentFormat = $params[0];
        $requestedFormat = $params[1];
        $value = filter_var($value, FILTER_SANITIZE_STRING);
        $date = \DateTime::createFromFormat($currentFormat, $value);
        if(($date && $date->format($currentFormat) == $value) == false){
            throw new \Exception('Failed to filter and format date. Incorrect date format.');
        }
        if(empty($date)){
            throw new \Exception('Failed to filter and format date. Value is either empty or format inserted in wrong.');
        }
        return $date->format($requestedFormat);
    }

    /**
     * Convert MS Word special characters to web safe characters.
     * [“, ”, ‘, ’, –, …] => [", ", ', ', -, ...]
     *
     * @param string $value
     * @param array $params
     *
     * @return string
     */
    protected function filter_ms_word_characters($value, $params = null)
    {
        $word_open_double = '“';
        $word_close_double = '”';
        $web_safe_double = '"';

        $value = str_replace(array($word_open_double, $word_close_double), $web_safe_double, $value);

        $word_open_single = '‘';
        $word_close_single = '’';
        $web_safe_single = "'";

        $value = str_replace(array($word_open_single, $word_close_single), $web_safe_single, $value);

        $word_em = '–';
        $web_safe_em = '-';

        $value = str_replace($word_em, $web_safe_em, $value);

        $word_ellipsis = '…';
        $web_safe_em = '...';

        $value = str_replace($word_ellipsis, $web_safe_em, $value);

        return $value;
    }

    // ** ------------------------- Validators ------------------------------------ ** //


    /**
     * Verify that a value is contained within the pre-defined value set.
     *
     * Usage: '<index>' => 'contains,value value value'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_contains($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        $param = trim(strtolower($param));

        $value = trim(strtolower($input[$field]));

        if (preg_match_all('#\'(.+?)\'#', $param, $matches, PREG_PATTERN_ORDER)) {
            $param = $matches[1];
        } else {
            $param = explode(chr(32), $param);
        }

        if (in_array($value, $param)) { // valid, return nothing
            return;
        }

        return array(
            'field' => $field,
            'value' => $value,
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Verify that a value is contained within the pre-defined value set.
     * OUTPUT: will NOT show the list of values.
     *
     * Usage: '<index>' => 'contains_list,value;value;value'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_contains_list($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $param = trim(strtolower($param));

        $value = trim(strtolower($input[$field]));

        $param = explode(';', $param);

        // consider: in_array(strtolower($value), array_map('strtolower', $param)

        if (in_array($value, $param)) { // valid, return nothing
            return;
        } else {
            return array(
                'field' => $field,
                'value' => $value,
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Verify that a value is NOT contained within the pre-defined value set.
     * OUTPUT: will NOT show the list of values.
     *
     * Usage: '<index>' => 'doesnt_contain_list,value;value;value'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_doesnt_contain_list($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $param = trim(strtolower($param));

        $value = trim(strtolower($input[$field]));

        $param = explode(';', $param);

        if (!in_array($value, $param)) { // valid, return nothing
            return;
        } else {
            return array(
                'field' => $field,
                'value' => $value,
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Check if the specified key is present and not empty.
     *
     * Usage: '<index>' => 'required'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_required($field, $input, $param = null)
    {
        if (isset($input[$field]) && ($input[$field] === false || $input[$field] === 0 || $input[$field] === 0.0 || $input[$field] === '0' || !empty($input[$field]))) {
            return;
        }

        return array(
            'field' => $field,
            'value' => null,
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }


    /**
     * @param $field
     * @param $input
     * @param null $param
     * @return array|void
     */
    protected function validate_required_special($field, $input, $param = null){
        $empty = false;

        if (isset($input[$field]) && ($input[$field] === false || $input[$field] === 0 || $input[$field] === 0.0 || $input[$field] === '0' || !empty($input[$field]))) {
            $empty = true;
        }

        if (isset($input[$param]) && ($input[$param] === false || $input[$param] === 0 || $input[$param] === 0.0 || $input[$param] === '0' || !empty($input[$param]))) {
            if($empty === true){
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => null,
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }


    /**
     * Determine if the provided email is valid.
     *
     * Usage: '<index>' => 'valid_email'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_valid_email($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value length is less or equal to a specific value.
     *
     * Usage: '<index>' => 'max_len,240'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_max_len($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) <= (int)$param) {
                return;
            }
        } else {
            if (strlen($input[$field]) <= (int)$param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value length is more or equal to a specific value.
     *
     * Usage: '<index>' => 'min_len,4'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_min_len($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) >= (int)$param) {
                return;
            }
        } else {
            if (strlen($input[$field]) >= (int)$param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value length matches a specific value.
     *
     * Usage: '<index>' => 'exact_len,5'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_exact_len($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) == (int)$param) {
                return;
            }
        } else {
            if (strlen($input[$field]) == (int)$param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value contains only alpha characters.
     *
     * Usage: '<index>' => 'alpha'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_alpha($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match('/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha-numeric characters.
     *
     * Usage: '<index>' => 'alpha_numeric'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_alpha_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha characters with dashed and underscores.
     *
     * Usage: '<index>' => 'alpha_dash'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_alpha_dash($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i', $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha numeric characters with spaces.
     *
     * Usage: '<index>' => 'alpha_numeric_space'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_alpha_numeric_space($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }
        //if (!preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ._-\s])+$/i", $input[$field]) !== false) {
        //if (!preg_match("/^[a-z0-9 .\-\:\_;\']+$/i", $input[$field]) !== false) {
        if (!preg_match("/^[a-z0-9 :,.!().?;\_\'-]+$/i", $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha numeric characters with spaces.
     *
     * Usage: '<index>' => 'alpha_space'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_alpha_space($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i", $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid number or numeric string.
     *
     * Usage: '<index>' => 'numeric'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!is_numeric($input[$field])) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid integer.
     *
     * Usage: '<index>' => 'integer'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_integer($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (filter_var($input[$field], FILTER_VALIDATE_INT) === false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a PHP accepted boolean.
     *
     * Usage: '<index>' => 'boolean'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_boolean($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field]) && $input[$field] !== 0) {
            return;
        }

        $booleans = array('1', 'true', true, 1, '0', 'false', false, 0, 'yes', 'no', 'on', 'off');
        if (in_array($input[$field], $booleans, true)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value is a valid float.
     *
     * Usage: '<index>' => 'float'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_float($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (filter_var($input[$field], FILTER_VALIDATE_FLOAT) === false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid URL.
     *
     * Usage: '<index>' => 'valid_url'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_valid_url($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_URL)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if a URL exists & is accessible.
     *
     * Usage: '<index>' => 'url_exists'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_url_exists($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $url = parse_url(strtolower($input[$field]));

        if (isset($url['host'])) {
            $url = $url['host'];
        }

        if (function_exists('checkdnsrr')) {
            if (checkdnsrr($url) === false) {
                return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule' => __FUNCTION__,
                    'param' => $param,
                );
            }
        } else {
            if (gethostbyname($url) == $url) {
                return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule' => __FUNCTION__,
                    'param' => $param,
                );
            }
        }
    }

    /**
     * Determine if the provided value is a valid IP address.
     *
     * Usage: '<index>' => 'valid_ip'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_valid_ip($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_IP) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid IPv4 address.
     *
     * Usage: '<index>' => 'valid_ipv4'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     *
     * @see http://pastebin.com/UvUPPYK0
     */

    /*
     * What about private networks? http://en.wikipedia.org/wiki/Private_network
     * What about loop-back address? 127.0.0.1
     */
    protected function validate_valid_ipv4($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // removed !== FALSE

            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid IPv6 address.
     *
     * Usage: '<index>' => 'valid_ipv6'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_valid_ipv6($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the input is a valid credit card number.
     *
     * See: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
     * Usage: '<index>' => 'valid_cc'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_valid_cc($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $number = preg_replace('/\D/', '', $input[$field]);

        if (function_exists('mb_strlen')) {
            $number_length = mb_strlen($number);
        } else {
            $number_length = strlen($number);
        }

        $parity = $number_length % 2;

        $total = 0;

        for ($i = 0; $i < $number_length; ++$i) {
            $digit = $number[$i];

            if ($i % 2 == $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        if ($total % 10 == 0) {
            return; // Valid
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the input is a valid human name [Credits to http://github.com/ben-s].
     *
     * See: https://github.com/Wixel/GUMP/issues/5
     * Usage: '<index>' => 'valid_name'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_valid_name($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i", $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided input is likely to be a street address using weak detection.
     *
     * Usage: '<index>' => 'street_address'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_street_address($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        // Theory: 1 number, 1 or more spaces, 1 or more words
        $hasLetter = preg_match('/[a-zA-Z]/', $input[$field]);
        $hasDigit = preg_match('/\d/', $input[$field]);
        $hasSpace = preg_match('/\s/', $input[$field]);

        $passes = $hasLetter && $hasDigit && $hasSpace;

        if (!$passes) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid IBAN.
     *
     * Usage: '<index>' => 'iban'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_iban($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        static $character = array(
            'A' => 10, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16,
            'H' => 17, 'I' => 18, 'J' => 19, 'K' => 20, 'L' => 21, 'M' => 22,
            'N' => 23, 'O' => 24, 'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28,
            'T' => 29, 'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34,
            'Z' => 35, 'B' => 11
        );

        if (!preg_match("/\A[A-Z]{2}\d{2} ?[A-Z\d]{4}( ?\d{4}){1,} ?\d{1,4}\z/", $input[$field])) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }

        $iban = str_replace(' ', '', $input[$field]);
        $iban = substr($iban, 4) . substr($iban, 0, 4);
        $iban = strtr($iban, $character);

        if (bcmod($iban, 97) != 1) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided input is a valid date (ISO 8601).
     *
     * Usage: '<index>' => 'date'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_valid_date($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $format = $param;
        $value = filter_var($input[$field], FILTER_SANITIZE_STRING);
        $date = \DateTime::createFromFormat($format, $value);

        if($date && $date->format($format) === $value) {
            return;
        }else{
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided input meets age requirement (ISO 8601).
     *
     * Usage: '<index>' => 'min_age,13'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param int
     *
     * @return mixed
     */
    protected function validate_min_age($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $cdate1 = new DateTime(date('Y-m-d', strtotime($input[$field])));
        $today = new DateTime(date('d-m-Y'));

        $interval = $cdate1->diff($today);
        $age = $interval->y;

        if ($age <= $param) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided numeric value is lower or equal to a specific value.
     *
     * Usage: '<index>' => 'max_numeric,50'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     *
     * @return mixed
     */
    protected function validate_max_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (is_numeric($input[$field]) && is_numeric($param) && ($input[$field] <= $param)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided numeric value is higher or equal to a specific value.
     *
     * Usage: '<index>' => 'min_numeric,1'
     *
     * @param string $field
     * @param array $input
     * @param null $param
     * @return mixed
     */
    protected function validate_min_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (is_numeric($input[$field]) && is_numeric($param) && ($input[$field] >= $param)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value starts with param.
     *
     * Usage: '<index>' => 'starts,Z'
     *
     * @param string $field
     * @param array $input
     *
     * @return mixed
     */
    protected function validate_starts($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (strpos($input[$field], $param) !== 0) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Checks if a file was uploaded.
     *
     * Usage: '<index>' => 'required_file'
     *
     * @param  string $field
     * @param  array $input
     *
     * @return mixed
     */
    protected function validate_required_file($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (is_array($input[$field]) && $input[$field]['error'] !== 4) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Check the uploaded file for extension for now
     * checks only the ext should add mime type check.
     *
     * Usage: '<index>' => 'extension,png;jpg;gif
     *
     * @param string $field
     * @param array $input
     * @param $param
     *
     * @return mixed
     */
    protected function validate_extension($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (is_array($input[$field]) && $input[$field]['error'] !== 4) {
            $param = trim(strtolower($param));
            $allowed_extensions = explode(';', $param);

            $path_info = pathinfo($input[$field]['name']);
            $extension = $path_info['extension'];

            if (in_array($extension, $allowed_extensions)) {
                return;
            }

            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided field value equals current field value.
     *
     *
     * Usage: '<index>' => 'equalsfield,Z'
     *
     * @param string $field
     * @param string $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_equalsfield($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if ($input[$field] == $input[$param]) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided field value is a valid GUID (v4)
     *
     * Usage: '<index>' => 'guidv4'
     *
     * @param string $field
     * @param string $input
     * @param string $param field to compare with
     * @return mixed
     */
    protected function validate_guidv4($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (preg_match("/\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/", $input[$field])) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Trims whitespace only when the value is a scalar.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function trimScalar($value)
    {
        if (is_scalar($value)) {
            $value = trim($value);
        }

        return $value;
    }


    /**
     * Custom regex validator.
     *
     * Usage: '<index>' => 'regex,/your-regex-expression/'
     *
     * @param string $field
     * @param array $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_regex($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $regex = $param;
        if (!preg_match($regex, $input[$field])) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * JSON validator.
     *
     * Usage: '<index>' => 'valid_json_string'
     *
     * @param string $field
     * @param array $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_valid_json_string($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!is_string($input[$field]) || !is_object(json_decode($input[$field]))) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Check if an input is an array and if the size is more or equal to a specific value.
     *
     * Usage: '<index>' => 'valid_array_size_greater,1'
     *
     * @param string $field
     * @param array $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_valid_array_size_greater($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!is_array($input[$field]) || sizeof($input[$field]) < (int)$param) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Check if an input is an array and if the size is less or equal to a specific value.
     *
     * Usage: '<index>' => 'valid_array_size_lesser,1'
     *
     * @param string $field
     * @param array $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_valid_array_size_lesser($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!is_array($input[$field]) || sizeof($input[$field]) > (int)$param) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Check if an input is an array and if the size is equal to a specific value.
     *
     * Usage: '<index>' => 'valid_array_size_equal,1'
     *
     * @param string $field
     * @param array $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_valid_array_size_equal($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!is_array($input[$field]) || sizeof($input[$field]) == (int)$param) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }


    /**
     * @param $field
     * @param $input
     * @param null $param
     * @return bool|mixed|string
     */
    protected function validate_valid_mobile_phone($field, $input, $param = null){
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }
        $lengthCheck = (!empty($param) && $param === 'true') ? true : false;
        $mobileNumber = $input[$field];
        $mobileNumber = str_replace('O', '0', $mobileNumber); //Replace Letter O with number 0 (just in case)
        $mobileNumber = trim(preg_replace('/[^0-9]/','', $mobileNumber));//Remove all non-digits

        //Length Validation
        if($lengthCheck === true && (strlen($mobileNumber) > 15 || strlen($mobileNumber) < 8)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }

        //Country code sanitization
        if(!empty($countryCode)) {
            $countryCode = trim(preg_replace('/[^0-9]/', '', $countryCode));//Remove all non-digits
        }

        //If we have specified no country code, and the number starts with a 0, it is invalid
        if(empty($countryCode) && (strpos($mobileNumber, '0') === 0) &&  (strpos($mobileNumber, '0') !== false)){
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }

        //Check to see if the country code already matches the default
        if(!empty($countryCode)){
            $inCCLength = strlen(strval($countryCode));
            $inCC = strval($countryCode);
            $currentCC = substr($mobileNumber, 0, $inCCLength);
            if($currentCC === $inCC){
                //Same as switch below, but we need to do it here too
                if($currentCC === '61' && $lengthCheck){
                    if(strlen($mobileNumber) !== 11){
                        return array(
                            'field' => $field,
                            'value' => $input[$field],
                            'rule' => __FUNCTION__,
                            'param' => $param,
                        );
                    }
                }
                return $mobileNumber;
            }

            //If the first number is a 0, we must add the country code.
            if((strpos($mobileNumber, "0") === 0)&&(strpos($mobileNumber, "0") !== false)) {
                $mobileNumber = $countryCode.substr($mobileNumber, 1);
            }
            else {
                $mobileNumber = $countryCode . $mobileNumber;
            }
        }

        if(empty($prefixList)){
            $prefixList = Lookup::loadCountryDialingCodeList();
        }

        //Check the country code is valid.
        $internationalDiallingCode = null;
        for($i = 0; $i <= 4; $i++){
            $idnCode = intval(substr($mobileNumber, 0, $i));
            if(isset($prefixList[$idnCode])){
                $internationalDiallingCode = $idnCode;
            }
        }

        //For some country codes, mobile numbers are an exact length, none longer, or shorter.
        //AU->61 is one of those CC's
        if($lengthCheck && $internationalDiallingCode){
            switch($internationalDiallingCode){
                case 61:
                    if(strlen($mobileNumber) !== 11){
                        return array(
                            'field' => $field,
                            'value' => $input[$field],
                            'rule' => __FUNCTION__,
                            'param' => $param,
                        );
                    }
                    break;
                default:
                    break;
            }
        }

        if(!empty($internationalDiallingCode)){
            return $mobileNumber;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

}