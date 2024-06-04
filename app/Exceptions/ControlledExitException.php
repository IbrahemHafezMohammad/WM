<?php

namespace App\Exceptions;

use Exception;

class ControlledExitException extends Exception
{
    public function __construct($message = "Controlled Exit From Code Logic", $code = 0, Exception $previous = null) {
        
        parent::__construct($message, $code, $previous);
    }
}