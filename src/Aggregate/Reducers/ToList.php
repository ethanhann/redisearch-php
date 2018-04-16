<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class ToList extends AbstractReducer
{
    public function getDefinition(): string
    {
        return "TOLIST 1 {$this->property}";
    }
}
