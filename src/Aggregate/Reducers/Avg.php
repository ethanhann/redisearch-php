<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Avg extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'AVG';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
