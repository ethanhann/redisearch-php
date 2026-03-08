<?php

namespace Ehann\Tests\RediSearch\Exceptions;

use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use PHPUnit\Framework\TestCase;

class NoFieldsInIndexExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage(): void
    {
        // Arrange
        $expected = 'There needs to be at least one field defined as a property in the index.';

        // Act
        $message = (new NoFieldsInIndexException())->getMessage();

        // Assert
        $this->assertSame($expected, $message);
    }
}
