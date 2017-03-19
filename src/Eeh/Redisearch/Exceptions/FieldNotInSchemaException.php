<?php

namespace Eeh\RediSearch\Exceptions;

use Exception;

class FieldNotInSchemaException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(trim("The field is not a property in the index. $message"), $code, $previous);
    }
}