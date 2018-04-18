<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinctApproximate extends AbstractReducer
{
    public function toArray(): array
    {
        return ['COUNT_DISTINCTISH', '1', $this->fieldName];
    }
}
