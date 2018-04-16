<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Quantile extends AbstractReducer
{
    public $quantile;

    public function __construct(string $property, string $quantile)
    {
        parent::__construct($property);
        $this->quantile = $quantile;
    }

    public function getDefinition(): string
    {
        return "QUANTILE 2 {$this->property} {$this->quantile}";
    }
}
