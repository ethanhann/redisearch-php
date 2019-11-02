<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class UnknownIndexNameOrNameIsAnAliasItselfException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("Unknown index name (or name is an alias itself). $message"),
            $code,
            $previous
        );
    }
}
