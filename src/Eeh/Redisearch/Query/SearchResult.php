<?php

namespace Eeh\RediSearch\Query;

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

    public static function makeSearchResult(array $rawRediSearchResult, bool $documentsAsArray)
    {
        if (!$rawRediSearchResult) {
            return false;
        }

        if (count($rawRediSearchResult) === 1) {
            return new SearchResult(0, []);
        }

        $count = array_shift($rawRediSearchResult);
        $documents = [];
        for ($i = 0; $i <= $count; $i += 2) {
            $document = $documentsAsArray ? [] : new \stdClass();
            $documentsAsArray ?
                $document['id'] = $rawRediSearchResult[$i] :
                $document->id = $rawRediSearchResult[$i];
            $fields = $rawRediSearchResult[$i + 1];
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
