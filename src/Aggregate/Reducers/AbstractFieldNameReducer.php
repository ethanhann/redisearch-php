<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

use Ehann\RediSearch\CanBecomeArrayInterface;

abstract class AbstractFieldNameReducer implements CanBecomeArrayInterface
{
    use Aliasable;

    public $fieldName;
    protected $reducerKeyword;

    public function __construct(string $fieldName, string $alias = '')
    {
        $this->fieldName = $fieldName;
        $this->alias = $alias;
    }

    public function toArray(): array
    {
        return [];
    }

    protected function makeAlias(): string
    {
        return empty($alias) ? strtolower($this->reducerKeyword) . "_" . $this->fieldName : $this->alias;
    }
}
