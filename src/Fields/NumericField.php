<?php

namespace Ehann\RediSearch\Fields;

class NumericField extends AbstractField
{
    use Sortable;

    public function getType(): string
    {
        return 'NUMERIC';
    }

    public function getTypeDefinition(): array
    {
        $properties = parent::getTypeDefinition();
        if ($this->isSortable()) {
            $properties[] = 'SORTABLE';
        }
        return $properties;
    }
}
