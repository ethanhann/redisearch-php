<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;

interface BuilderInterface
{
    public function load(array $properties): BuilderInterface;
    public function groupBy(string $property, ReducerInterface $reducer = null): BuilderInterface;
    public function sortBy(array $properties, integer $max = -1): BuilderInterface;
    public function apply(string $expression, string $name): BuilderInterface;
    public function limit(int $offset, int $pageSize = 10): BuilderInterface;
    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult;
}
