<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class RawCommandErrorException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(trim("The Redis client threw an exception. See the inner exception for details. $message"), $code, $previous);
    }
}
