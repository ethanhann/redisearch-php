<?php

namespace Ehann\Tests\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\InvalidRedisClientClassException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Redis\RedisClientInterface;
use Ehann\Tests\AbstractTestCase;
use Predis\Client;
use Redis;

class RedisClientTest extends AbstractTestCase
{
    public function testShouldThrowUnknownIndexNameExceptionIfIndexDoesNotExist()
    {
        $this->redisClient->flushAll();
        $this->expectException(UnknownIndexNameException::class);

        $this->redisClient->rawCommand('FT.INFO', ['DOES_NOT_EXIST']);
    }
}
