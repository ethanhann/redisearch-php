<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class FirstValue extends AbstractFieldNameReducer
{
    protected $reducerKeyword = 'FIRST_VALUE';
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
            ['REDUCE', $this->reducerKeyword, '1', $this->fieldName, 'AS', $this->makeAlias()] :
            ['REDUCE', $this->reducerKeyword, '4', $this->fieldName, 'BY', $this->byFieldName, $this->isAscending ? 'ASC' : 'DESC', 'AS', $this->makeAlias()];
    }
}
