<?php

namespace Eeh\Redisearch\Query;

use Eeh\Redisearch\Query\SearchResult;
use Redis;

class Builder implements BuilderInterface
{
    protected $numericFilters = [];
    protected $geoFilters = [];
    protected $redis;
    /** @var string */
    private $indexName;

    public function __construct(Redis $redis, string $indexName)
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
            $this->callCommand(array_merge(['FT.SEARCH', $this->indexName, $query], $this->numericFilters)),
            $documentsAsArray
        );
    }

    protected function callCommand(array $args)
    {
//        print PHP_EOL . implode(' ', $args) . PHP_EOL;
        return call_user_func_array([$this->redis, 'rawCommand'], $args);
    }
}
