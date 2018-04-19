<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

use Ehann\RediSearch\CanBecomeArrayInterface;

abstract class AbstractFieldNameReducer implements CanBecomeArrayInterface
{
    public $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public function toArray(): array
    {
        return [];
    }
}
