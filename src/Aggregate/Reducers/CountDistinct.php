<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinct extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'COUNT_DISTINCT';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
