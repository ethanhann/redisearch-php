<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;

interface BuilderInterface
{
    public function load(array $fieldNames): BuilderInterface;
    public function groupBy($fieldName, ReducerInterface $reducer = null): BuilderInterface;
    public function reduce(ReducerInterface $reducer): BuilderInterface;
    public function sortBy($fieldName, int $max = -1): BuilderInterface;
    public function apply(string $expression, string $asName): BuilderInterface;
    public function limit(int $offset, int $pageSize = 10): BuilderInterface;
    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult;
    public function avg($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function count(string $fieldName, int $group): BuilderInterface;
    public function countDistinct($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function countDistinctApproximate($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function sum($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function max($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function min($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function standardDeviation($fieldName, string $reduceByFieldName = null): BuilderInterface;
    public function firstValue(string $fieldName, string $byFieldName = null, bool $isAscending = true): BuilderInterface;
    public function quantile(string $fieldName, string $quantile): BuilderInterface;
    public function toList(string $fieldName): BuilderInterface;
}
