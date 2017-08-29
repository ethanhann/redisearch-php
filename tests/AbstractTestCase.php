<?php

namespace Ehann\Tests;

use Ehann\RediSearch\Redis\RedisClient;
use PHPUnit\Framework\TestCase;
use Predis\Client;

abstract class AbstractTestCase extends TestCase
{
    /** @var string */
    protected $indexName;
    /** @var RedisClient */
    protected $redisClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        if (getenv('REDIS_LIBRARY') == 'Predis') {
            $redis = new Client([
                'scheme' => 'tcp',
                'host' => getenv('REDIS_HOST') ?? '127.0.0.1',
                'port' => getenv('REDIS_PORT') ?? 6379,
                'database' => getenv('REDIS_DB') ?? 0,
            ]);
            $redis->connect();
            $this->redisClient = new RedisClient($redis);
        } else {
            $this->redisClient = new RedisClient(
                \Redis::class,
                getenv('REDIS_HOST') ?? '127.0.0.1',
                getenv('REDIS_PORT') ?? 6379,
                getenv('REDIS_DB') ?? 0
            );
        }
    }
}
