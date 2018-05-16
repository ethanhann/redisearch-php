<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinctApproximate extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['REDUCE', 'COUNT_DISTINCTISH', '1', $this->fieldName];
    }
}
