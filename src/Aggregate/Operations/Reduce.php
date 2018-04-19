<?php

namespace Ehann\RediSearch\Aggregate\Operations;

use Ehann\RediSearch\CanBecomeArrayInterface;

class Reduce implements CanBecomeArrayInterface
{
    public $reducer;

    public function __construct(CanBecomeArrayInterface $reducer)
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
