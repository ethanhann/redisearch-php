<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RedisRaw\Exceptions\RedisRawCommandException;
use PHPUnit\Framework\TestCase;

class RedisRawCommandExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage(): void
    {
        // Arrange
        $command = 'FT.SEARCH MyIndex foo';
        $expected = "Redis Raw Command Failed. $command";

        // Act
        $message = (new RedisRawCommandException($command))->getMessage();

        // Assert
        $this->assertSame($expected, $message);
    }
}
