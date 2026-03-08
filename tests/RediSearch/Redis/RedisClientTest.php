<?php

namespace Ehann\Tests\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\Tests\RediSearchTestCase;

class RedisClientTest extends RediSearchTestCase
{
    public function testShouldThrowUnknownIndexNameExceptionIfIndexDoesNotExist(): void
    {
        // Arrange
        $this->redisClient->flushAll();

        // Assert
        $this->expectException(UnknownIndexNameException::class);

        // Act
        $this->redisClient->rawCommand('FT.INFO', ['DOES_NOT_EXIST']);
    }
}
