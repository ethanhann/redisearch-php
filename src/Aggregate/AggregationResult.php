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
        array_shift($rawRediSearchResult);
        $documents = [];
        for ($i = 0; $i < count($rawRediSearchResult); $i += $documentWidth) {
            $document = $documentsAsArray ? [] : new \stdClass();
            $fields = $rawRediSearchResult[$i + ($documentWidth - 1)];
            if (is_array($fields)) {
                for ($j = 0; $j < count($fields); $j += 2) {
                    $normalizedKey = preg_replace("/[^A-Za-z0-9 ]/", '_', $fields[$j]);
                    if ($normalizedKey !== '_') {
                        // Avoid a situation where the key is empty by only trimming the key if it is not "_".
                        $normalizedKey = trim($normalizedKey, '_');
                    }
                    $documentsAsArray ?
                        $document[$normalizedKey] = $fields[$j + 1] :
                        $document->$normalizedKey = $fields[$j + 1];

                    if (strpos($fields[$j], '(')) {
                        $normalizedKeyField = $normalizedKey . '_field';
                        $documentsAsArray ?
                            $document[$normalizedKeyField] = $fields[$j] :
                            $document->$normalizedKeyField = $fields[$j];
                    }
                }
            }
            $documents[] = $document;
        }
        return new AggregationResult(count($documents), $documents);
    }
}
