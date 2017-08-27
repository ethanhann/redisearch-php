<?php

namespace Ehann\Tests;

use Ehann\RediSearch\Redis\RedisClient;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    /** @var string */
    protected $indexName;
    /** @var RedisClient */
    protected $redisClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->redisClient = new RedisClient(
            \Redis::class,
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }
}
