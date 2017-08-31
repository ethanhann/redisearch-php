<?php

namespace Ehann\RediSearch\Query;

use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Redis\RedisClient;
use Exception;
use InvalidArgumentException;

class Builder implements BuilderInterface
{
    const GEO_FILTER_UNITS = ['m', 'km', 'mi', 'ft'];

    protected $limit = '';
    protected $slop = null;
    protected $verbatim = '';
    protected $withScores = '';
    protected $withPayloads = '';
    protected $noStopWords = '';
    protected $noContent = '';
    protected $inFields = '';
    protected $inKeys = '';
    protected $numericFilters = [];
    protected $geoFilters = [];
    protected $sortBy = '';
    protected $redis;
    /** @var string */
    private $indexName;

    public function __construct(RedisClient $redis, string $indexName)
    {
        $this->redis = $redis;
        $this->indexName = $indexName;
    }

    public function noContent(): BuilderInterface
    {
        $this->noContent = 'NOCONTENT';
        return $this;
    }

    public function limit(int $offset, int $pageSize = 10): BuilderInterface
    {
        $this->limit = "LIMIT $offset $pageSize";
        return $this;
    }

    public function inFields(int $number, array $fields): BuilderInterface
    {
        $this->inFields = "INFIELDS $number {implode(' ', $fields)}";
        return $this;
    }

    public function inKeys(int $number, array $keys): BuilderInterface
    {
        $this->inKeys = "INKEYS $number {implode(' ', $keys)}";
        return $this;
    }

    public function slop(int $slop): BuilderInterface
    {
        $this->slop = "SLOP $slop";
        return $this;
    }

    public function noStopWords(): BuilderInterface
    {
        $this->noStopWords = 'NOSTOPWORDS';
        return $this;
    }

    public function withPayloads(): BuilderInterface
    {
        $this->withPayloads = 'WITHPAYLOADS';
        return $this;
    }

    public function withScores(): BuilderInterface
    {
        $this->withScores = 'WITHSCORES';
        return $this;
    }

    public function verbatim(): BuilderInterface
    {
        $this->verbatim = 'VERBATIM';
        return $this;
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

    public function sortBy(string $fieldName, $order = 'ASC'): BuilderInterface
    {
        $this->sortBy = "SORTBY $fieldName $order";
        return $this;
    }

    public function search(string $query, bool $documentsAsArray = false): SearchResult
    {
        $args = array_filter(
            array_merge(
                [$this->indexName, $query],
                explode(' ', $this->limit),
                explode(' ', $this->slop),
                [
                    $this->verbatim,
                    $this->withScores,
                    $this->withPayloads,
                    $this->noStopWords,
                    $this->noContent,
                ],
                explode(' ', $this->inFields),
                explode(' ', $this->inKeys),
                $this->numericFilters,
                explode(' ', array_reduce($this->geoFilters, function ($previous, $next) {
                    return $previous . $next;
                })),
                explode(' ', $this->sortBy)
            ),
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        );

        $rawResult = $this->redis->rawCommand('FT.SEARCH', $args);
        if (is_string($rawResult)) {
            if ($rawResult === 'Unknown Index name') {
                throw new UnknownIndexNameException();
            } else {
                throw new Exception($rawResult);
            }
        }

        return $rawResult ? SearchResult::makeSearchResult(
            $rawResult,
            $documentsAsArray,
            $this->withScores !== '',
            $this->withPayloads !== '',
            $this->noContent !== ''
        ) : new SearchResult(0, []);
    }
}
