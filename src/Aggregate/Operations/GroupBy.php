<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class GroupBy extends AbstractFieldNameOperation
{
    public function __construct(array $fieldNames)
    {
        parent::__construct('GROUPBY', $fieldNames);
    }
}
