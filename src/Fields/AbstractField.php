<?php

namespace Ehann\RediSearch\Fields;

abstract class AbstractField implements FieldInterface
{
    protected $name;
    protected $value;
    protected $sortable;

    public function __construct(string $name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable)
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function getTypeDefinition(): array
    {
        return [
            $this->getName(),
            $this->getType(),
        ];
    }
}
