<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class FirstValue extends AbstractReducer
{
    public $byfieldName;
    public $isAscending;

    public function __construct(string $fieldName, string $byfieldName = null, bool $isAscending = true)
    {
        parent::__construct($fieldName);
        $this->byfieldName = $byfieldName;
        $this->isAscending = $isAscending;
    }

    public function getDefinition(): string
    {
        if (is_null($this->byfieldName)) {
            return "TOLIST 1 {$this->fieldName}";
        }

        $sortOrder = $this->isAscending ? 'ASC' : 'DESC';
        return "TOLIST 4 {$this->fieldName} BY {$this->fieldName} $sortOrder";
    }
}
