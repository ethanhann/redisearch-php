<?php

namespace Ehann\RediSearch\Fields;

class NumericField extends AbstractField
{
    use Sortable;
    use Noindex;

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
        if ($this->isNoindex()) {
            $properties[] = 'NOINDEX';
        }
        return $properties;
    }
}
