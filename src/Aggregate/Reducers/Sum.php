<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Sum extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "SUM 1 {$this->fieldName}";
    }
}
