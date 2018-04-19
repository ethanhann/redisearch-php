<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

use Ehann\RediSearch\CanBecomeArrayInterface;

class Count implements CanBecomeArrayInterface
{
    private $group;

    public function __construct(int $group)
    {
        $this->group = $group;
    }

    public function toArray(): array
    {
        return ['COUNT', $this->group];
    }
}
