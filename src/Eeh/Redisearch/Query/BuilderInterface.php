<?php

namespace Eeh\Redisearch\Query;

interface BuilderInterface
{
    public function numericFilter(string $fieldName, $min, $max = null): BuilderInterface;
    public function geoFilter(string $fieldName, float $longitude, float $latitude, float $radius, string $distanceUnit = 'km'): BuilderInterface;
    public function search(string $query, bool $documentsAsArray = false): SearchResult;
}
