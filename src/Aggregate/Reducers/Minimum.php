<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Minimum extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "MIN 1 {$this->property}";
    }
}
