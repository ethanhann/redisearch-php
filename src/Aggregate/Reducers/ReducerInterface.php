<?php

namespace Ehann\RediSearch\Aggregate\Reducers;

interface ReducerInterface
{
    public function toArray(): array;
}
