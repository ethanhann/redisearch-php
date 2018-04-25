<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Max extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['REDUCE', 'MAX', '1', $this->fieldName];
    }
}
