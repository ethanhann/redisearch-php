<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Redis\RedisClientInterface;

abstract class AbstractIndex
{
    /** @var RedisClientInterface */
    protected $redisClient;
    /** @var string */
    protected $indexName;

    public function __construct(RedisClientInterface $redisClient = null, string $indexName = '')
    {
        $this->redisClient = $redisClient;
        $this->indexName = $indexName;
    }
}
