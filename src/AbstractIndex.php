<?php

namespace Ehann\RediSearch;

use Ehann\RedisRaw\RedisRawClientInterface;

abstract class AbstractIndex
{
    /** @var RedisRawClientInterface */
    protected $redisClient;
    /** @var string */
    protected $indexName;

    public function __construct(RedisRawClientInterface $redisClient = null, string $indexName = '')
    {
        $this->redisClient = $redisClient;
        $this->indexName = $indexName;
    }
}
