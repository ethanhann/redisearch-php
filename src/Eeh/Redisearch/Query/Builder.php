<?php

namespace Eeh\Redisearch\Query;

use Eeh\Redisearch\Redis\RedisClient;

class Builder implements BuilderInterface
{
    protected $numericFilters = [];
    protected $geoFilters = [];
    protected $redis;
    /** @var string */
    private $indexName;

    public function __construct(RedisClient $redis, string $indexName)
    {
        $this->redis = $redis;
        $this->indexName = $indexName;
    }

    public function filter(string $fieldName, $min, $max): BuilderInterface
    {
        $this->numericFilters[] = "FILTER $fieldName $min $max";
        return $this;
    }

    public function search(string $query, bool $documentsAsArray = false): SearchResult
    {
        return SearchResult::makeSearchResult(
            $this->redis->rawCommand('FT.SEARCH', array_merge([$this->indexName, $query], $this->numericFilters)),
            $documentsAsArray
        );
    }
}
