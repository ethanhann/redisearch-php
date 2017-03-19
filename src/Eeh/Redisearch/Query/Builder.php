<?php

namespace Eeh\Redisearch\Query;

use Eeh\Redisearch\Exceptions\UnknownIndexNameException;
use Eeh\Redisearch\Redis\RedisClient;
use Exception;

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

    public function filter(string $fieldName, $min, $max = null): BuilderInterface
    {
        $max = $max ?? $min;
        $this->numericFilters[] = "FILTER $fieldName $min $max";
        return $this;
    }

    public function search(string $query, bool $documentsAsArray = false): SearchResult
    {
        $rawResult = $this->redis->rawCommand('FT.SEARCH', array_merge([$this->indexName, $query], $this->numericFilters));
        if (is_string($rawResult)) {
            if ($rawResult === 'Unknown Index name') {
                throw new UnknownIndexNameException();
            } else {
                throw new Exception($rawResult);
            }
        }
        return SearchResult::makeSearchResult($rawResult, $documentsAsArray);
    }
}
