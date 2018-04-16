<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class StandardDeviation extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "STDDEV 1 {$this->fieldName}";
    }
}
