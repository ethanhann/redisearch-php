<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Quantile extends AbstractReducer
{
    public $quantile;

    public function __construct(string $fieldName, string $quantile)
    {
        parent::__construct($fieldName);
        $this->quantile = $quantile;
    }

    public function getDefinition(): string
    {
        return "QUANTILE 2 {$this->fieldName} {$this->quantile}";
    }
}
