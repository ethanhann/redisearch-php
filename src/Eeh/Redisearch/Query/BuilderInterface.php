<?php

namespace Eeh\Redisearch\Query;

interface BuilderInterface
{
    public function filter(string $fieldName, $min, $max = null): BuilderInterface;
    public function search(string $query, bool $documentsAsArray = false): SearchResult;
}
