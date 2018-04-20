<?php

namespace Ehann\Tests\RediSearch\Redis;

use Ehann\RedisRaw\Exceptions\UnknownIndexNameException;
use Ehann\Tests\AbstractTestCase;

class RedisClientTest extends AbstractTestCase
{
    public function testShouldThrowUnknownIndexNameExceptionIfIndexDoesNotExist()
    {
        $this->redisClient->flushAll();
        $this->expectException(UnknownIndexNameException::class);

        $this->redisClient->rawCommand('FT.INFO', ['DOES_NOT_EXIST']);
    }
}
