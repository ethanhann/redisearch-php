<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class Load extends AbstractFieldNameOperation
{
    public function __construct(array $fieldNames)
    {
        parent::__construct('LOAD', $fieldNames);
    }
}
