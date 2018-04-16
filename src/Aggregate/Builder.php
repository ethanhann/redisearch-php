<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Reducers\FirstValue;
use Ehann\RediSearch\Aggregate\Reducers\Maximum;
use Ehann\RediSearch\Aggregate\Reducers\Minimum;
use Ehann\RediSearch\Aggregate\Reducers\Quantile;
use Ehann\RediSearch\Aggregate\Reducers\StandardDeviation;
use Ehann\RediSearch\Aggregate\Reducers\Sum;
use Ehann\RediSearch\Aggregate\Reducers\ToList;
use Ehann\RediSearch\Exceptions\RedisRawCommandException;
use Ehann\RediSearch\Redis\RedisClientInterface;
use Ehann\RediSearch\Aggregate\Reducers\Average;
use Ehann\RediSearch\Aggregate\Reducers\Count;
use Ehann\RediSearch\Aggregate\Reducers\CountDistinct;
use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;

class Builder implements BuilderInterface
{
    protected $redis;
    private $indexName;
    private $load;
    private $groupBy;
    private $sortBy;
    private $apply;
    private $limit;

    public function __construct(RedisClientInterface $redis, string $indexName)
    {
        $this->redis = $redis;
        $this->indexName = $indexName;
    }

    /**
     * Only use this method if absolutely necessary. It has a detrimental impact on performance.
     * @param array $properties
     * @return BuilderInterface
     */
    public function load(array $properties): BuilderInterface
    {
        $count = count($properties);
        $implodedProperties = implode(' ', $properties);
        $this->load[] = "LOAD $count $implodedProperties";
        return $this;
    }

    public function groupBy(string $fieldName, ReducerInterface $reducer = null): BuilderInterface
    {
        $reduce = (is_null($reducer) ? '' : " REDUCE " . $reducer->getDefinition());
        $this->groupBy[] = trim("GROUPBY $fieldName $reduce");
        return $this;
    }

    public function groupByWithManyReducers(string $fieldName, array $reducers = null): BuilderInterface
    {
        $reduce = '';
        if (!is_null($reducers)) {
            foreach ($reducers as $reducer) {
                if ($reducer instanceof ReducerInterface) {
                    $reduce .= " REDUCE " . $reducer->getDefinition();
                }
            }
        }
        $this->groupBy[] = trim("GROUPBY $fieldName $reduce");
        return $this;
    }

    public function average(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new Average($fieldName));
    }

    public function count(string $fieldName, int $group): BuilderInterface
    {
        return $this->groupBy($fieldName, new Count($group));
    }

    public function countDistinct(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new CountDistinct($fieldName));
    }

    public function countDistinctApproximate(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new Average($fieldName));
    }

    public function firstValue(string $fieldName, string $byFieldName = null, bool $isAscending = true): BuilderInterface
    {
        return $this->groupBy($fieldName, new FirstValue($fieldName, $byFieldName, $isAscending));
    }

    public function sum(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new Sum($fieldName));
    }

    public function max(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new Maximum($fieldName));
    }

    public function min(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new Minimum($fieldName));
    }

    public function quantile(string $fieldName, string $quantile): BuilderInterface
    {
        return $this->groupBy($fieldName, new Quantile($fieldName, $quantile));
    }

    public function standardDeviation(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new StandardDeviation($fieldName));
    }

    public function toList(string $fieldName): BuilderInterface
    {
        return $this->groupBy($fieldName, new ToList($fieldName));
    }

    public function sortBy(array $properties, integer $max = -1): BuilderInterface
    {
        $count = count($properties);
        $implodedProperties = implode(' ', $properties);
        $this->sortBy[] = "SORTBY $count $implodedProperties";
        return $this;
    }

    /**
     * @param string $expression An expression that can be used to perform arithmetic operations on numeric properties.
     * @param string $name The name of the fieldName to add or replace.
     * @return BuilderInterface
     */
    public function apply(string $expression, string $name): BuilderInterface
    {
        $this->apply[] = "APPLY $expression as $name";
        return $this;
    }

    public function limit(int $offset, int $pageSize = 10): BuilderInterface
    {
        $this->limit[] = "LIMIT $offset $pageSize";
        return $this;
    }

    public function makeAggregateCommandArguments(string $query): array
    {
        return array_filter(
            array_merge(
                trim($query) === '' ? [$this->indexName] : [$this->indexName, $query],
                explode(' ', $this->load),
                $this->groupBy,
                $this->sortBy,
                $this->apply,
                $this->limit
            ),
            function ($item) {
                return !is_null($item) && $item !== '';
            }
        );
    }

    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult
    {
        $args = $this->makeAggregateCommandArguments($query);
        $rawResult = $this->redis->rawCommand(
            'FT.AGGREGATE',
            $args
        );
        if (is_string($rawResult)) {
            throw new RedisRawCommandException("Result: $rawResult, Query: $query");
        }

//        return $rawResult ? SearchResult::makeSearchResult(
//            $rawResult,
//            $documentsAsArray,
//            $this->withScores !== '',
//            $this->withPayloads !== '',
//            $this->noContent !== ''
//        ) : new SearchResult(0, []);
        return new AggregationResult();
    }
}
