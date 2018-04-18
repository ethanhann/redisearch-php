<?php

namespace Ehann\RediSearch\Aggregate\Operations;

class Limit implements OperationInterface
{
    private $offset;
    private $pageSize;

    public function __construct(int $offset, int $pageSize)
    {
        $this->offset = $offset;
        $this->pageSize = $pageSize;
    }

    public function toArray(): array
    {
        return ['LIMIT', $this->offset, $this->pageSize];
    }
}
