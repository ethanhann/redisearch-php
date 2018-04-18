<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Operations\Apply;
use Ehann\RediSearch\Aggregate\Operations\GroupBy;
use Ehann\RediSearch\Aggregate\Operations\Limit;
use Ehann\RediSearch\Aggregate\Operations\Load;
use Ehann\RediSearch\Aggregate\Operations\OperationInterface;
use Ehann\RediSearch\Aggregate\Operations\Reduce;
use Ehann\RediSearch\Aggregate\Operations\SortBy;
use Ehann\RediSearch\Aggregate\Reducers\CountDistinctApproximate;
use Ehann\RediSearch\Aggregate\Reducers\FirstValue;
use Ehann\RediSearch\Aggregate\Reducers\Max;
use Ehann\RediSearch\Aggregate\Reducers\Min;
use Ehann\RediSearch\Aggregate\Reducers\Quantile;
use Ehann\RediSearch\Aggregate\Reducers\StandardDeviation;
use Ehann\RediSearch\Aggregate\Reducers\Sum;
use Ehann\RediSearch\Aggregate\Reducers\ToList;
use Ehann\RediSearch\Exceptions\RedisRawCommandException;
use Ehann\RediSearch\Redis\RedisClientInterface;
use Ehann\RediSearch\Aggregate\Reducers\Avg;
use Ehann\RediSearch\Aggregate\Reducers\Count;
use Ehann\RediSearch\Aggregate\Reducers\CountDistinct;
use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;

class Builder implements BuilderInterface
{
    protected $redis;
    private $indexName = '';
    protected $pipeline = [];
    private $load = [];


    public function __construct(RedisClientInterface $redis, string $indexName)
    {
        $this->redis = $redis;
        $this->indexName = $indexName;
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
     * @param ReducerInterface|array $reducer
     * @return BuilderInterface
     */
    public function groupBy($fieldName, ReducerInterface $reducer = null): BuilderInterface
    {
        $this->pipeline[] = new GroupBy(is_array($fieldName) ? $fieldName : [$fieldName]);
        if (!is_null($reducer)) {
            $this->reduce($reducer);
        }
        return $this;
    }

    /**
     * @param ReducerInterface $reducer
     * @return BuilderInterface
     */
    public function reduce(ReducerInterface $reducer): BuilderInterface
    {
        $this->pipeline[] = new Reduce($reducer);
        return $this;
    }

    /**
     * @param array|string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function avg($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new Avg(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param string $fieldName
     * @param int $group
     * @return BuilderInterface
     */
    public function count(string $fieldName, int $group): BuilderInterface
    {
        return $this->groupBy($fieldName, new Count($group));
    }

    /**
     * @param array|string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function countDistinct($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new CountDistinct(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param array|string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function countDistinctApproximate($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new CountDistinctApproximate(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function sum($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new Sum(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function max($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new Max(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function min($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new Min(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param string $fieldName
     * @param string $quantile
     * @return BuilderInterface
     */
    public function quantile(string $fieldName, string $quantile): BuilderInterface
    {
        return $this->groupBy($fieldName, new Quantile($fieldName, $quantile));
    }

    /**
     * @param string $fieldName
     * @param string|null $reduceByFieldName
     * @return BuilderInterface
     */
    public function standardDeviation($fieldName, string $reduceByFieldName = null): BuilderInterface
    {
        return $this->groupBy($fieldName, new StandardDeviation(is_null($reduceByFieldName) ? $fieldName : $reduceByFieldName));
    }

    /**
     * @param string $fieldName
     * @param string|null $byFieldName
     * @param bool $isAscending
     * @return BuilderInterface
     */
    public function firstValue(string $fieldName, string $byFieldName = null, bool $isAscending = true): BuilderInterface
    {
        return $this->groupBy($fieldName, new FirstValue($fieldName, $byFieldName, $isAscending));
    }

    /**
     * @param string $fieldName
     * @return BuilderInterface
     */
    public function toList(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new ToList($fieldName));
    }

    /**
     * @param array|string $fieldName
     * @param int $max
     * @return BuilderInterface
     */
    public function sortBy($fieldName, int $max = -1): BuilderInterface
    {
        $this->pipeline[] = new SortBy(is_array($fieldName) ? $fieldName : [$fieldName]);
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
        $pipelineOperations = array_map(function (OperationInterface $operation) {
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
        if (is_string($rawResult)) {
            throw new RedisRawCommandException("Result: $rawResult, Query: $query");
        }

        return $rawResult ? AggregationResult::makeAggregationResult(
            $rawResult,
            $documentsAsArray
        ) : new AggregationResult(0, []);
    }
}
