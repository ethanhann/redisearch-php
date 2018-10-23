<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

use Ehann\RediSearch\CanBecomeArrayInterface;

abstract class AbstractFieldNameReducer implements CanBecomeArrayInterface
{
    public $fieldName;
    public $alias;

    public function __construct(string $fieldName, string $alias = '')
    {
        $this->fieldName = $fieldName;
        $this->alias = $alias;
    }

    public function toArray(): array
    {
        return [];
    }

    protected function addAlias($params) {
        if(empty($this->alias)) {
            return $params;
        }

        return array_merge($params, ['AS', $this->alias]);
    }
}
