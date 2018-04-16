<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Average extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "AVG 1 {$this->fieldName}";
    }
}
