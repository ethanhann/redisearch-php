<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class AbstractReducer implements ReducerInterface
{
    public $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getDefinition(): string
    {
        return '';
    }
}
