<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class UnknownIndexNameException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("Unknown index name. $message"),
            $code,
            $previous
        );
    }
}
