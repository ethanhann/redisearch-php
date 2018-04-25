<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Min extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['REDUCE', 'MIN', '1', $this->fieldName];
    }
}
