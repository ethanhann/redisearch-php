<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Sum implements ReducerInterface
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return "SUM 1 {$this->property}";
    }
}
