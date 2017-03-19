<?php

namespace Eeh\Redisearch\Exceptions;

use Exception;

class UnknownIndexNameException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("Only Predis\\Client and Redis client classes are allowed. $message"),
            $code,
            $previous
        );
    }
}
