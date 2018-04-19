<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinct extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['COUNT_DISTINCT', '1', $this->fieldName];
    }
}
