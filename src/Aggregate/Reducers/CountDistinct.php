<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class CountDistinct extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return $this->addAlias(['REDUCE', 'COUNT_DISTINCT', '1', $this->fieldName]);
    }
}
