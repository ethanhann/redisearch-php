<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinctApproximate extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "COUNT_DISTINCTISH 1 {$this->property}";
    }
}
