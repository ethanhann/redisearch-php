<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;

interface BuilderInterface
{
    public function load(array $properties): BuilderInterface;
    public function groupBy(string $fieldName, ReducerInterface $reducer = null): BuilderInterface;
    public function sortBy(array $properties, integer $max = -1): BuilderInterface;
    public function apply(string $expression, string $name): BuilderInterface;
    public function limit(int $offset, int $pageSize = 10): BuilderInterface;
    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult;
    public function average(string $fieldName): BuilderInterface;
    public function count(string $fieldName, int $group): BuilderInterface;
    public function countDistinct(string $fieldName): BuilderInterface;
    public function countDistinctApproximate(string $fieldName): BuilderInterface;
    public function firstValue(string $fieldName, string $byFieldName = null, bool $isAscending = true): BuilderInterface;
    public function sum(string $fieldName): BuilderInterface;
    public function max(string $fieldName): BuilderInterface;
    public function min(string $fieldName): BuilderInterface;
    public function quantile(string $fieldName, string $quantile): BuilderInterface;
    public function standardDeviation(string $fieldName): BuilderInterface;
    public function toList(string $fieldName): BuilderInterface;
}
