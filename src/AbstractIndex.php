<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Redis\RedisClient;

abstract class AbstractIndex
{
    /** @var RedisClient */
    protected $redisClient;
    /** @var string */
    protected $indexName;

    public function __construct(RedisClient $redisClient = null, string $indexName = '')
    {
        $this->redisClient = $redisClient ?? new RedisClient();
        $this->indexName = $indexName;
    }
}
