<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class FirstValue extends AbstractReducer
{
    public $byFieldName;
    public $isAscending;

    public function __construct(string $fieldName, string $byFieldName = null, bool $isAscending = true)
    {
        parent::__construct($fieldName);
        $this->byFieldName = $byFieldName;
        $this->isAscending = $isAscending;
    }

    public function getDefinition(): string
    {
        if (is_null($this->byFieldName)) {
            return "TOLIST 1 {$this->fieldName}";
        }

        $sortOrder = $this->isAscending ? 'ASC' : 'DESC';
        return "TOLIST 4 {$this->fieldName} BY {$this->byFieldName} $sortOrder";
    }
}
