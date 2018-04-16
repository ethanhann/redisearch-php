<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class StandardDeviation implements ReducerInterface
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return "STDDEV 1 {$this->property}";
    }
}
