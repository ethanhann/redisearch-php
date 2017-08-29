<?php

namespace Ehann\Tests\RediSearch\Exceptions;

use Ehann\RediSearch\Exceptions\FieldNotInSchemaException;
use PHPUnit\Framework\TestCase;

class FieldNotInSchemaExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage()
    {
        $expected = 'The field is not a property in the index.';

        $message = (new FieldNotInSchemaException())->getMessage();

        $this->assertEquals($expected, $message);
    }
}
