<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class ToList implements ReducerInterface
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return "TOLIST 1 {$this->property}";
    }
}
