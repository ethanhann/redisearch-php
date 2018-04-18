<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class SortBy implements OperationInterface
{
    public $fieldNames = [];

    public function __construct(array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
    }

    public function toArray(): array
    {
        $count = count($this->fieldNames);
        return $count > 0 ? array_merge(
            ["SORTBY", $count],
            implode(' ', array_map(function ($fieldName) {
                return "@$fieldName";
            }, $this->fieldNames))
        ) : [];
    }
}
