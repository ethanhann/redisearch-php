<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class SortBy extends AbstractFieldNameOperation
{
    public function __construct(array $fieldNames)
    {
        parent::__construct('SORTBY', $fieldNames);
    }
}
