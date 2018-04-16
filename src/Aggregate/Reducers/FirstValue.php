<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class FirstValue extends AbstractReducer
{
    public $byProperty;
    public $isAscending;

    public function __construct(string $property, string $byProperty = null, bool $isAscending = true)
    {
        parent::__construct($property);
        $this->byProperty = $byProperty;
        $this->isAscending = $isAscending;
    }

    public function getDefinition(): string
    {
        if (is_null($this->byProperty)) {
            return "TOLIST 1 {$this->property}";
        }

        $sortOrder = $this->isAscending ? 'ASC' : 'DESC';
        return "TOLIST 4 {$this->property} BY {$this->property} $sortOrder";
    }
}
