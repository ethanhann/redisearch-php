<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinctApproximate extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'COUNT_DISTINCTISH';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
