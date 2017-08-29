<?php

namespace Ehann\Tests\RediSearch\Exceptions;

use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use PHPUnit\Framework\TestCase;

class NoFieldsInIndexExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage()
    {
        $expected = 'There needs to be at least one field defined as a property in the index.';

        $message = (new NoFieldsInIndexException())->getMessage();

        $this->assertEquals($expected, $message);
    }
}
