<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Quantile implements ReducerInterface
{
    private $property;
    private $quantile;

    public function __construct(string $property, string $quantile)
    {
        $this->property = $property;
        $this->quantile = $quantile;
    }

    public function getDefinition(): string
    {
        return "QUANTILE 2 {$this->property} {$this->quantile}";
    }
}
