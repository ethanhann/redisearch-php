<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Avg extends AbstractReducer
{
    public function toArray(): array
    {
        return ['AVG', '1', $this->fieldName];
    }
}
