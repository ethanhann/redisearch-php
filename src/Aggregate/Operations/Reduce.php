<?php

namespace Ehann\RediSearch\Aggregate\Operations;

use Ehann\RediSearch\Aggregate\Reducers\ReducerInterface;

class Reduce implements OperationInterface
{
    public $reducer;

    public function __construct(ReducerInterface $reducer)
    {
        $this->reducer = $reducer;
    }

    public function toArray(): array
    {
        $definition = $this->reducer->toArray();
        array_unshift($definition, "REDUCE");
        return $definition;
    }
}
