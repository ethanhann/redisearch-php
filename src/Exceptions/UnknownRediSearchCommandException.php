<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class UnknownRediSearchCommandException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("Unknown RediSearch command. Are you sure the RediSearch module is enabled in Redis? $message"),
            $code,
            $previous
        );
    }
}
