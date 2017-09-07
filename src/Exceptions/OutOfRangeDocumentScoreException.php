<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class OutOfRangeDocumentScoreException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(trim("Document scores must be normalized between 0.0 ... 1.0. $message"), $code, $previous);
    }
}
