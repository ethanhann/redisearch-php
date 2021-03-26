<?php

namespace Ehann\RediSearch\Aggregate;

use Ehann\RediSearch\CanBecomeArrayInterface;

interface BuilderInterface
{
    public function load(array $fieldNames): BuilderInterface;
    public function groupBy($fieldName, CanBecomeArrayInterface $reducer = null): BuilderInterface;
    public function reduce(CanBecomeArrayInterface $reducer): BuilderInterface;
    public function sortBy($fieldName, $isAscending = true, int $max = -1): BuilderInterface;
    public function apply(string $expression, string $asName): BuilderInterface;
    public function filter(string $expression): BuilderInterface;
    public function limit(int $offset, int $pageSize = 10): BuilderInterface;
    public function search(string $query = '', bool $documentsAsArray = false): AggregationResult;
    public function avg(string $fieldName): BuilderInterface;
    public function count(int $group = 0): BuilderInterface;
    public function countDistinct(string $fieldName): BuilderInterface;
    public function countDistinctApproximate(string $fieldName): BuilderInterface;
    public function sum(string $fieldName): BuilderInterface;
    public function max(string $fieldName): BuilderInterface;
    public function min(string $fieldName): BuilderInterface;
    public function standardDeviation(string $fieldName): BuilderInterface;
    public function firstValue(string $fieldName, string $byFieldName = null, bool $isAscending = true): BuilderInterface;
    public function quantile(string $fieldName, float $quantile): BuilderInterface;
    public function toList(string $fieldName): BuilderInterface;
}
