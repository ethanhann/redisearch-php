<?php

namespace Ehann\Tests\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\Tests\RediSearchTestCase;

class RedisClientTest extends RediSearchTestCase
{
    public function testShouldThrowUnknownIndexNameExceptionIfIndexDoesNotExist()
    {
        $this->redisClient->flushAll();
        $this->expectException(UnknownIndexNameException::class);

        $this->redisClient->rawCommand('FT.INFO', ['DOES_NOT_EXIST']);
    }
}
