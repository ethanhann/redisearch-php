<?php

namespace Ehann\RediSearch\Aggregate\Operations;

abstract class AbstractFieldNameOperation implements OperationInterface
{
    protected $operationName;
    protected $fieldNames;

    public function __construct(string $operationName, array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
        $this->operationName = $operationName;
    }

    public function toArray(): array
    {
        $count = count($this->fieldNames);
        return $count > 0 ? array_merge([$this->operationName, $count],
            array_map(function ($fieldName) {
                return "@$fieldName";
            }, $this->fieldNames)) : [];
    }
}

