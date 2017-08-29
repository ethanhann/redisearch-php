<?php

namespace Ehann\RediSearch\Fields;

class TextField extends AbstractField
{
    protected $weight = 1.0;

    public function getType(): string
    {
        return 'TEXT';
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight)
    {
        $this->weight = $weight;
        return $this;
    }

    public function getTypeDefinition(): array
    {
        $properties = parent::getTypeDefinition();
        $properties[] = 'WEIGHT';
        $properties[] = $this->getWeight();
        return $properties;
    }
}
