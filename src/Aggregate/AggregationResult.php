<?php

namespace Ehann\RediSearch\Aggregate;

class AggregationResult
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

    public static function makeAggregationResult(array $rawRediSearchResult, bool $documentsAsArray)
    {
        if (!$rawRediSearchResult) {
            return false;
        }

        $documentWidth = 1;
        $count = array_shift($rawRediSearchResult);
        $documents = [];
        for ($i = 0; $i < count($rawRediSearchResult); $i += $documentWidth) {
            $document = $documentsAsArray ? [] : new \stdClass();
            $fields = $rawRediSearchResult[$i + ($documentWidth - 1)];
            if (is_array($fields)) {
                for ($j = 0; $j < count($fields); $j += 2) {
                    $documentsAsArray ?
                        $document[$fields[$j]] = $fields[$j + 1] :
                        $document->{$fields[$j]} = $fields[$j + 1];
                }
            }
            $documents[] = $document;
        }
        return new AggregationResult($count, $documents);
    }
}
