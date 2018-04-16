<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinct extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "COUNT_DISTINCT 1 {$this->property}";
    }
}
