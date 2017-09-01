<?php

namespace Ehann\Tests\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\InvalidRedisClientClassException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Redis\RedisClient;
use Ehann\Tests\AbstractTestCase;
use Predis\Client;
use Redis;

class RedisClientTest extends AbstractTestCase
{
    /**
     * @requires extension redis
     */
    public function testShouldConnectUsingPhpRedisInstance()
    {
        $client = new Redis();
        $client->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379
        );
        $client->select(getenv('REDIS_DB') ?? 0);

        $redisClient = new RedisClient($client);

        $this->assertTrue($redisClient->isPhpRedis());
        $this->assertFalse($redisClient->isPredisClient());
    }

    /**
     * @requires extension redis
     */
    public function testShouldConnectUsingPhpRedisClassName()
    {
        $redisClient = new RedisClient(Redis::class);

        $this->assertTrue($redisClient->isPhpRedis());
        $this->assertFalse($redisClient->isPredisClient());
    }

    public function testShouldConnectUsingPredisInstance()
    {
        $client = new Client([
            'scheme' => 'tcp',
            'host' => getenv('REDIS_HOST') ?? '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?? 6379,
            'database' => getenv('REDIS_DB') ?? 0,
        ]);
        $client->connect();

        $redisClient = new RedisClient($client);

        $this->assertTrue($redisClient->isPredisClient());
        $this->assertFalse($redisClient->isPhpRedis());
    }

    public function testShouldConnectUsingPredisClassName()
    {
        $redisClient = new RedisClient(
            Client::class,
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );

        $this->assertTrue($redisClient->isPredisClient());
        $this->assertFalse($redisClient->isPhpRedis());
    }

    public function testShouldThrowInvalidRedisClientClassException()
    {
        $this->expectException(InvalidRedisClientClassException::class);

        new RedisClient('foo');
    }

    public function testShouldThrowUnknownIndexNameExceptionIfIndexDoesNotExist()
    {
        $this->redisClient->flushAll();
        $this->expectException(UnknownIndexNameException::class);

        $this->redisClient->rawCommand('FT.INFO', ['DOES_NOT_EXIST']);
    }
}
