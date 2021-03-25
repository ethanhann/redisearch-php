<?php

namespace Ehann\RediSearch\Exceptions;

use Exception;

class DocumentAlreadyInIndexException extends Exception
{
    public function __construct($indexName, $documentId, $code = 0, Exception $previous = null)
    {
        parent::__construct("Document ($documentId) already in index ($indexName).", $code, $previous);
    }
}
