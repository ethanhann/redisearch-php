<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Max extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'MAX';

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()];
    }
}
