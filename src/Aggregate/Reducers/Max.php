<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Max extends AbstractReducer
{
    public function toArray(): array
    {
        return ['MAX', '1', $this->fieldName];
    }
}
