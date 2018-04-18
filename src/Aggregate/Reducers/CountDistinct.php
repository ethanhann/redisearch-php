<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinct extends AbstractReducer
{
    public function toArray(): array
    {
        return ['COUNT_DISTINCT', '1', $this->fieldName];
    }
}
