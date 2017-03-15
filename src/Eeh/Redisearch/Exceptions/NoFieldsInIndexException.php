<?php

namespace Eeh\Redisearch\Exceptions;

use Exception;

class NoFieldsInIndexException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if (!is_string($message) || $message === '') {
            $message = 'There needs to be at least one field defined in the index.';
        }
        parent::__construct($message, $code, $previous);
    }
}