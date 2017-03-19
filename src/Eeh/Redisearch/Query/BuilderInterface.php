<?php

namespace Eeh\RediSearch\Query;

interface BuilderInterface
{
    public function noContent(): BuilderInterface;
    public function limit(int $offset, int $pageSize = 10): BuilderInterface;
    public function inFields(int $number, array $fields): BuilderInterface;
    public function inKeys(int $number, array $keys): BuilderInterface;
    public function slop(int $slop): BuilderInterface;
    public function noStopWords(): BuilderInterface;
    public function withPayloads(): BuilderInterface;
    public function withScores(): BuilderInterface;
    public function verbatim(): BuilderInterface;
    public function numericFilter(string $fieldName, $min, $max = null): BuilderInterface;
    public function geoFilter(string $fieldName, float $longitude, float $latitude, float $radius, string $distanceUnit = 'km'): BuilderInterface;
    public function search(string $query, bool $documentsAsArray = false): SearchResult;
}
