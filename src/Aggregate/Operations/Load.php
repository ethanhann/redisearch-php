<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class Load implements OperationInterface
{
    public $fieldNames;

    public function __construct(array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
    }

    public function toArray(): array
    {
        return array_merge(
            ['LOAD', count($this->fieldNames)],
            array_map(function ($fieldName) {
                return "@$fieldName";
            }, $this->fieldNames)
        );
    }
}
