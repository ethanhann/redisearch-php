<?php

namespace Ehann\RediSearch\Query;

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


    public static function makeSearchResult(
        array $rawRediSearchResult,
        bool $documentsAsArray,
        bool $withScores = false,
        bool $withPayloads = false,
        bool $noContent = false
    ) {
        $documentWidth = $noContent ? 1 : 2;

        if (!$rawRediSearchResult) {
            return false;
        }

        if (count($rawRediSearchResult) === 1) {
            return new SearchResult(0, []);
        }

        if ($withScores) {
            $documentWidth++;
        }

        if ($withPayloads) {
            $documentWidth++;
        }

        $count = array_shift($rawRediSearchResult);
        $documents = [];
        for ($i = 0; $i < count($rawRediSearchResult); $i += $documentWidth) {
            $document = $documentsAsArray ? [] : new \stdClass();
            $documentsAsArray ?
                $document['id'] = $rawRediSearchResult[$i] :
                $document->id = $rawRediSearchResult[$i];
            if ($withScores) {
                $documentsAsArray ?
                    $document['score'] = $rawRediSearchResult[$i+1] :
                    $document->score = $rawRediSearchResult[$i+1];
            }
            if ($withPayloads) {
                $j = $withScores ? 2 : 1;
                $documentsAsArray ?
                    $document['payload'] = $rawRediSearchResult[$i+$j] :
                    $document->payload = $rawRediSearchResult[$i+$j];
            }
            if (!$noContent) {
                $fields = $rawRediSearchResult[$i + ($documentWidth - 1)];
                for ($j = 0; $j < count($fields); $j += 2) {
                    $documentsAsArray ?
                        $document[$fields[$j]] = $fields[$j + 1] :
                        $document->{$fields[$j]} = $fields[$j + 1];
                }
            }
            $documents[] = $document;
        }
        return new SearchResult($count, $documents);
    }
}
