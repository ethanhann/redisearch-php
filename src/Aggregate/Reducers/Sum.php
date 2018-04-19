<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Sum extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['REDUCE', 'SUM', '1', $this->fieldName];
    }
}
