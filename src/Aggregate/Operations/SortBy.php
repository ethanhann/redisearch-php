<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class SortBy extends AbstractFieldNameOperation
{
    protected $isAscending;
    protected $max;

    public function __construct(array $fieldNames, $isAscending = true, int $max = -1)
    {
        parent::__construct('SORTBY', $fieldNames);
        $this->isAscending = $isAscending;
        $this->max = $max;
    }

    public function toArray(): array
    {
        $options = [
            $this->isAscending ? 'ASC' : 'DESC'
        ];
        $count = count($this->fieldNames) + count($options);
        if ($this->max >= 0) {
            $options[] = 'MAX';
            $options[] = $this->max;
        }
        return $count > 0 ? array_merge(
            [$this->operationName, $count],
            array_map(function ($fieldName) {
                return "@$fieldName";
            }, $this->fieldNames),
            $options
        ) : [];
    }
}
