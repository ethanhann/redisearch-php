<?php

namespace Ehann\RediSearch;

use Ehann\RedisRaw\RedisRawClientInterface;

abstract class AbstractIndex extends AbstractRediSearchClientAdapter
{
    /** @var string */
    protected $indexName;

    public function __construct(RedisRawClientInterface $redisClient = null, string $indexName = '')
    {
        parent::__construct($redisClient);
        $this->indexName = $indexName;
    }
}
