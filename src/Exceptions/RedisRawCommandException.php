<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class RedisRawCommandException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(trim("Redis Raw Command Failed. $message"), $code, $previous);
    }
}
