<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class DocumentAlreadyInIndexException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(
            trim("Document already in index. $message"),
            $code,
            $previous
        );
    }
}
