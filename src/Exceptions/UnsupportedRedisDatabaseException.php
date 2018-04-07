<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class UnsupportedRedisDatabaseException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(trim("Only database 0 is supported by the RediSearch module. $message"), $code, $previous);
    }
}
