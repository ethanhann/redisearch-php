<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Sum extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'SUM';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
