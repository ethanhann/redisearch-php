<?php

namespace Eeh\Redisearch\Query;

use Eeh\Redisearch\Exceptions\UnknownIndexNameException;
use Eeh\Redisearch\Redis\RedisClient;
use Exception;
use InvalidArgumentException;

class Builder implements BuilderInterface
{
    const GEO_FILTER_UNITS = ['m', 'km', 'mi', 'ft'];

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

    public function numericFilter(string $fieldName, $min, $max = null): BuilderInterface
    {
        $max = $max ?? $min;
        $this->numericFilters[] = "FILTER $fieldName $min $max";
        return $this;
    }

    public function geoFilter(string $fieldName, float $longitude, float $latitude, float $radius, string $distanceUnit = 'km'): BuilderInterface
    {
        if (!in_array($distanceUnit, self::GEO_FILTER_UNITS)) {
            throw new InvalidArgumentException($distanceUnit);
        }

        $this->geoFilters[] = "GEOFILTER $fieldName $longitude $latitude $radius $distanceUnit";
        return $this;
    }

    public function search(string $query, bool $documentsAsArray = false): SearchResult
    {
        $rawResult = $this->redis->rawCommand(
            'FT.SEARCH',
            array_merge([$this->indexName, $query], $this->numericFilters, $this->geoFilters)
        );
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
