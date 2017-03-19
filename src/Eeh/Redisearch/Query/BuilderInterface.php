<?php

namespace Eeh\Redisearch\Query;

use Eeh\Redisearch\Query\SearchResult;

interface BuilderInterface
{
    public function filter(string $fieldName, $min, $max): BuilderInterface;
    public function search(string $query, bool $documentsAsArray = false): SearchResult;
}
