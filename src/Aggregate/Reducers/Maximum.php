<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Maximum implements ReducerInterface
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return "MAX 1 {$this->property}";
    }
}
