<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class ToList extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['TOLIST', '1', $this->fieldName];
    }
}
