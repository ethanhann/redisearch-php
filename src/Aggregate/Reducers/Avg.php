<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Avg extends AbstractFieldNameReducer
{
    public function toArray(): array
    {
        return ['AVG', '1', $this->fieldName];
    }
}
