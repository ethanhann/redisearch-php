<?php

namespace Eeh\Redisearch;

class SearchResult
{
    protected $count;
    protected $documents;

    public function __construct(int $count, array $documents)
    {
        $this->count = $count;
        $this->documents = $documents;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public static function makeSearchResult(array $rawRedisearchResult, bool $documentsAsArray)
    {
        if (!$rawRedisearchResult) {
            return false;
        }
        $count = array_shift($rawRedisearchResult);
        $documents = [];
        for ($i = 0; $i <= $count; $i += 2) {
            $document = $documentsAsArray ? [] : new \stdClass();
            $documentsAsArray ?
                $document['id'] = $rawRedisearchResult[$i] :
                $document->id = $rawRedisearchResult[$i];
            $fields = $rawRedisearchResult[$i + 1];
            for ($j = 0; $j < count($fields); $j += 2) {
                $documentsAsArray ?
                    $document[$fields[$j]] = $fields[$j + 1] :
                    $document->{$fields[$j]} = $fields[$j + 1];
            }
            $documents[] = $document;
        }
        return new SearchResult($count, $documents);
    }
}
