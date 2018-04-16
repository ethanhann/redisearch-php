<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Minimum implements ReducerInterface
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return "MIN 1 {$this->property}";
    }
}
