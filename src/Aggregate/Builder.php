<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Operations\Apply;
use Ehann\RediSearch\Aggregate\Operations\GroupBy;
use Ehann\RediSearch\Aggregate\Operations\Limit;
use Ehann\RediSearch\Aggregate\Operations\Load;
use Ehann\RediSearch\Aggregate\Operations\SortBy;
use Ehann\RediSearch\Aggregate\Reducers\CountDistinctApproximate;
use Ehann\RediSearch\Aggregate\Reducers\FirstValue;
use Ehann\RediSearch\Aggregate\Reducers\Max;
use Ehann\RediSearch\Aggregate\Reducers\Min;
use Ehann\RediSearch\Aggregate\Reducers\Quantile;
use Ehann\RediSearch\Aggregate\Reducers\StandardDeviation;
use Ehann\RediSearch\Aggregate\Reducers\Sum;
use Ehann\RediSearch\Aggregate\Reducers\ToList;
use Ehann\RediSearch\CanBecomeArrayInterface;
use Ehann\RedisRaw\Exceptions\RedisRawCommandException;
use Ehann\RediSearch\RediSearchRedisClient;
use Ehann\RediSearch\Aggregate\Reducers\Avg;
use Ehann\RediSearch\Aggregate\Reducers\Count;
use Ehann\RediSearch\Aggregate\Reducers\CountDistinct;

class Builder implements BuilderInterface
{
    protected $redis;
    private $indexName = '';
    protected $pipeline = [];
    private $load = [];


    public function __construct(RediSearchRedisClient $redis, string $indexName)
    {
        $this->redis = $redis;
        $this->indexName = $indexName;
    }

    /**
     * Get pipeline.
     */
    public function getPipeline(): array
    {
        return $this->pipeline;
    }

    /**
     * Delete all operations from the aggregation pipeline.
     */
    public function clear()
    {
        $this->pipeline = [];
    }

    /**
     * Only use this method if absolutely necessary. It has a detrimental impact on performance.
     * @param array $fieldNames
     * @return BuilderInterface
     */
    public function load(array $fieldNames): BuilderInterface
    {
        $this->pipeline[] = new Load($fieldNames);
        return $this;
    }

    /**
     * @param string|array $fieldName
     * @param CanBecomeArrayInterface|array $reducer
     * @return BuilderInterface
     */
    public function groupBy($fieldName = [], CanBecomeArrayInterface $reducer = null): BuilderInterface
    {
        $this->pipeline[] = new GroupBy(is_array($fieldName) ? $fieldName : [$fieldName]);
        if (!is_null($reducer)) {
            $this->reduce($reducer);
        }
        return $this;
    }

    /**
     * @param CanBecomeArrayInterface $reducer
     * @return BuilderInterface
     */
    public function reduce(CanBecomeArrayInterface $reducer): BuilderInterface
    {
        $this->pipeline[] = $reducer;
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function avg(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new Avg($fieldName);
        return $this;
    }

    /**
     * @param int $group
     * @return BuilderInterface
     */
    public function count(int $group = 0): BuilderInterface
    {
        $this->pipeline[] = new Count($group);
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function countDistinct(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new CountDistinct($fieldName);
        return $this;
    }

    /**
     * @param array|string $fieldName
     * @return BuilderInterface
     */
    public function countDistinctApproximate(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new CountDistinctApproximate($fieldName);
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function sum(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new Sum($fieldName);
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function max(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new Max($fieldName);
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function min(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new Min($fieldName);
        return $this;
    }

    /**
     * @param string $fieldName
     * @param float $quantile
     * @return BuilderInterface
     */
    public function quantile(string $fieldName, float $quantile): BuilderInterface
    {
        $this->pipeline[] = new Quantile($fieldName, $quantile);
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function standardDeviation(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new StandardDeviation($fieldName);
        return $this;
    }

    /**
     * @param string $fieldName
     * @param string|null $byFieldName
     * @param bool $isAscending
     * @return BuilderInterface
     */
    public function firstValue(string $fieldName, string $byFieldName = null, bool $isAscending = true): BuilderInterface
    {
        $this->pipeline[] = new FirstValue($fieldName, $byFieldName, $isAscending);
        return $this;
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function toList(string $fieldName): BuilderInterface
    {
        $this->pipeline[] = new ToList($fieldName);
        return $this;
    }

    /**
     * @param array|string $fieldName
     * @param bool $isAscending
     * @param int $max
     * @return BuilderInterface
     */
    public function sortBy($fieldName, $isAscending = true, int $max = -1): BuilderInterface
    {
        $this->pipeline[] = new SortBy(is_array($fieldName) ? $fieldName : [$fieldName], $isAscending, $max);
        return $this;
    }

    /**
     * @param string $expression An expression that can be used to perform arithmetic operations on numeric properties.
     * @param string $asFieldName The name of the fieldName to add or replace.
     * @return BuilderInterface
     */
    public function apply(string $expression, string $asFieldName): BuilderInterface
    {
        $this->pipeline[] = new Apply($expression, $asFieldName);
        return $this;
    }

    /**
     * @param int $offset
     * @param int $pageSize
     * @return BuilderInterface
     */
    public function limit(int $offset, int $pageSize = 10): BuilderInterface
    {
        $this->pipeline[] = new Limit($offset, $pageSize);
        return $this;
    }

    /**
     * @param string $query
     * @return array
     */
    public function makeAggregateCommandArguments(string $query): array
    {
        $pipelineOperations = array_map(function (CanBecomeArrayInterface $operation) {
            return $operation->toArray();
        }, $this->pipeline);

        $pipelineOperations = array_reduce($pipelineOperations, function ($prev, $next) {
            return is_null($prev) ? $next : array_merge($prev, $next);
        });

        return array_filter(
            array_merge(
                trim($query) === '' ? [$this->indexName] : [$this->indexName, $query],
                $this->load,
                $pipelineOperations
            ),
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        );
    }

    /**
     * @param string $query
     * @param bool $documentsAsArray
     * @return AggregationResult
     * @throws RedisRawCommandException
     */
    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult
    {
        $args = $this->makeAggregateCommandArguments($query === '' ? '*' : $query);
        $rawResult = $this->redis->rawCommand(
            'FT.AGGREGATE',
            $args
        );

        return $rawResult ? AggregationResult::makeAggregationResult(
            $rawResult,
            $documentsAsArray
        ) : new AggregationResult(0, []);
    }
}
