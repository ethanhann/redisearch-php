<?php

namespace Ehann\Tests;

use Ehann\RediSearch\Redis\RedisClient;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Redis;

abstract class AbstractTestCase extends TestCase
{
    /** @var string */
    protected $indexName;
    /** @var RedisClient */
    protected $redisClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->redisClient = getenv('REDIS_LIBRARY') === 'Predis' ?
            $this->makeRedisClientWithPredis() :
            $this->makeRedisClientWithPhpRedis();
    }

    protected function makeRedisClientWithPhpRedis(): RedisClient
    {
        $client = new Redis();
        $client->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379
        );
        $client->select(getenv('REDIS_DB') ?? 0);
        return new RedisClient($client);
    }

    protected function makeRedisClientWithPredis(): RedisClient
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host' => getenv('REDIS_HOST') ?? '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?? 6379,
            'database' => getenv('REDIS_DB') ?? 0,
        ]);
        $redis->connect();
        return new RedisClient($redis);
    }
}
