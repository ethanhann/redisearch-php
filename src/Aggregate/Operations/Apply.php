<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class Apply implements OperationInterface
{
    public $expression;
    public $asFieldName;

    public function __construct(string $expression, string $asFieldName)
    {
        $this->expression = $expression;
        $this->asFieldName = $asFieldName;
    }

    public function toArray(): array
    {
        return ['APPLY', $this->expression, 'AS', $this->asFieldName];
    }
}
