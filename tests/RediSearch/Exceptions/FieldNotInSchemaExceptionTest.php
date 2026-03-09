<?php

namespace Ehann\Tests\RediSearch\Exceptions;

use Ehann\RediSearch\Exceptions\FieldNotInSchemaException;
use PHPUnit\Framework\TestCase;

class FieldNotInSchemaExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage(): void
    {
        // Arrange
        $expected = 'The field is not a property in the index.';

        // Act
        $message = (new FieldNotInSchemaException())->getMessage();

        // Assert
        $this->assertSame($expected, $message);
    }
}
