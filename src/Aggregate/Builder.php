<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Reducers\Sum;
use Ehann\RediSearch\Exceptions\RedisRawCommandException;
use Ehann\RediSearch\Redis\RedisClientInterface;
use Ehann\RediSearch\Aggregate\AggregationResult;
use Ehann\RediSearch\Aggregate\Reducers\AbstractReducer;
use Ehann\RediSearch\Aggregate\Reducers\Average;
use Ehann\RediSearch\Aggregate\Reducers\Count;
use Ehann\RediSearch\Aggregate\Reducers\CountDistinct;
use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;
use InvalidArgumentException;

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

    public function groupBy(string $property, ReducerInterface $reducer = null): BuilderInterface
    {
        $this->groupBy[] = $property . (is_null($reducer) ? '' : " REDUCE " . $reducer->getDefinition());
        return $this;
    }

    public function groupByWithManyReducers(string $property, array $reducers = null): BuilderInterface
    {
        $reduce = '';
        if (!is_null($reducers)) {
            foreach ($reducers as $reducer) {
                if ($reducer instanceof ReducerInterface) {
                    $reduce .= " REDUCE " . $reducer->getDefinition();
                }
            }
        }
        $this->groupBy[] = $property . $reduce;
        return $this;
    }

    public function count(string $property, int $group): BuilderInterface
    {
        $this->groupBy($property, new Count($group));
        return $this;
    }

    public function countDistinct(string $property): BuilderInterface
    {
        $this->groupBy($property, new CountDistinct($property));
        return $this;
    }

    public function countDistinctApproximate(string $property): BuilderInterface
    {
        $this->groupBy($property, new Average($property));
        return $this;
    }

    public function sum(string $property): BuilderInterface
    {
        $this->groupBy($property, new Sum($property));
        return $this;
    }

    public function average(string $property): BuilderInterface
    {
        $this->groupBy($property, new Average($property));
        return $this;
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
     * @param string $name The name of the property to add or replace.
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

    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult
    {
        return new AggregationResult();
    }
}
