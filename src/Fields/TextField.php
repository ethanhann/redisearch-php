<?php

namespace Ehann\RediSearch\Fields;

class TextField extends AbstractField
{
    use Sortable;
    use Noindex;

    protected $weight = 1.0;
    protected $noStem = false;

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

    public function isNoStem(): bool
    {
        return $this->noStem;
    }

    public function setNoStem(bool $noStem): TextField
    {
        $this->noStem = $noStem;
        return $this;
    }

    public function getTypeDefinition(): array
    {
        $properties = parent::getTypeDefinition();
        if ($this->isNoStem()) {
            $properties[] = 'NOSTEM';
        }
        $properties[] = 'WEIGHT';
        $properties[] = $this->getWeight();
        if ($this->isSortable()) {
            $properties[] = 'SORTABLE';
        }
        if ($this->isNoindex()) {
            $properties[] = 'NOINDEX';
        }
        return $properties;
    }
}
