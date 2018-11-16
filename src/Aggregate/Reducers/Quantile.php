<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Quantile extends AbstractFieldNameReducer
{
    public $quantile;
    protected $reducerKeyword = 'QUANTILE';

    public function __construct(string $fieldName, float $quantile)
    {
        parent::__construct($fieldName);
        $this->quantile = $quantile;
    }

    public function toArray(): array
    {
        return ['REDUCE', $this->reducerKeyword, '2', $this->fieldName, $this->quantile, 'AS', $this->makeAlias()];
    }
}
