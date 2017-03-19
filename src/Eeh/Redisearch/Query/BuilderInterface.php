<?php

namespace Eeh\Redisearch\Query;

use Eeh\Redisearch\SearchResult;

interface BuilderInterface
{
    public function filter(string $fieldName, $min, $max): BuilderInterface;
    public function search(string $query, bool $documentsAsArray = false): SearchResult;
}
