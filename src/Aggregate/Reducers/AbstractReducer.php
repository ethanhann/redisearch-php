<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class AbstractReducer implements ReducerInterface
{
    public $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public function toArray(): array
    {
        return [];
    }
}
