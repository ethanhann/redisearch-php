<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinctApproximate implements ReducerInterface
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return "COUNT_DISTINCTISH 1 {$this->property}";
    }
}
