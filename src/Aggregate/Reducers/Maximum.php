<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Maximum extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "MAX 1 {$this->property}";
    }
}
