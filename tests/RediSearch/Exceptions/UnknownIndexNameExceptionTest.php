<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use PHPUnit\Framework\TestCase;

class UnknownIndexNameExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage(): void
    {
        // Arrange
        $indexName = 'MyIndex';
        $expected = "Unknown index name. $indexName";

        // Act
        $message = (new UnknownIndexNameException($indexName))->getMessage();

        // Assert
        $this->assertSame($expected, $message);
    }
}
