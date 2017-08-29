<?php

namespace Ehann\RediSearch\Fields;

abstract class AbstractField implements FieldInterface
{
    protected $name;
    protected $value;

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

    public function getTypeDefinition(): array
    {
        return [
            $this->getName(),
            $this->getType(),
        ];
    }
}
