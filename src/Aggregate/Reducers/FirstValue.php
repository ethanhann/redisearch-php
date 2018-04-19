<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class FirstValue extends AbstractFieldNameReducer
{
    public $byFieldName;
    public $isAscending;

    public function __construct(string $fieldName, string $byFieldName = null, bool $isAscending = true)
    {
        parent::__construct($fieldName);
        $this->byFieldName = $byFieldName;
        $this->isAscending = $isAscending;
    }

    public function toArray(): array
    {
        return is_null($this->byFieldName) ?
            ['FIRST_VALUE', '1', $this->fieldName] :
            ['FIRST_VALUE', '4', $this->fieldName, 'BY', $this->byFieldName, $this->isAscending ? 'ASC' : 'DESC'];
    }
}
