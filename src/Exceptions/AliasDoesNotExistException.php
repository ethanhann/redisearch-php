<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class AliasDoesNotExistException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("Alias does not exist. $message"),
            $code,
            $previous
        );
    }
}
