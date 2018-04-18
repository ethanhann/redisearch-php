<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Min extends AbstractReducer
{
    public function toArray(): array
    {
        return ['MIN', '1', $this->fieldName];
    }
}
