<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

class Count implements ReducerInterface
{
    private $group;

    public function __construct(int $group)
    {
        $this->group = $group;
    }

    public function getDefinition(): string
    {
        return "COUNT {$this->group}";
    }
}
