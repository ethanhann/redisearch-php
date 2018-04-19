<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class StandardDeviation extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['REDUCE', 'STDDEV', '1', $this->fieldName];
    }
}
