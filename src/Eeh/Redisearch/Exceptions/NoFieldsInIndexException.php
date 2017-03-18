<?php

namespace Eeh\Redisearch\Exceptions;

use Exception;

class NoFieldsInIndexException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("There needs to be at least one field defined as a property in the index. $message"),
            $code,
            $previous
        );
    }
}