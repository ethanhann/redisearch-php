<?php
namespace Ehann\RediSearch\Exceptions;

use Exception;

class UnsupportedRediSearchLanguageException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct(trim("Unsupported language. $message"), $code, $previous);
    }
}
