<?php

namespace Ehann\RediSearch\Fields;

trait Sortable
{
    protected $isSortable = false;

    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    public function setSortable(bool $sortable)
    {
        $this->isSortable = $sortable;
        return $this;
    }
}
