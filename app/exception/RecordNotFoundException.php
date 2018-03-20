<?php

namespace App\exception;

/**
 * Custom Exception class for the API applicaion
 *
 * Class RecordNotFoundException
 * @package App\exception
 */
class RecordNotFoundException extends \Exception{

    /**
     * RecordNotFoundException constructor.
     * @param $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }


    /**
     * custom string representation of object
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }


    /**
     * @return string
     */
    public function getExceptionType(){
        return 'RecordNotFoundException';
    }
}