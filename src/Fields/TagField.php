<?php

namespace Ehann\RediSearch\Fields;

class TagField extends AbstractField
{
    use Sortable;
    use Noindex;

    protected $separator = ',';

    public function getType(): string
    {
        return 'TAG';
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSeparator(string $separator)
    {
        $this->separator = $separator;
        return $this;
    }

    public function getTypeDefinition(): array
    {
        $properties = parent::getTypeDefinition();

        $properties[] = 'SEPARATOR';
        $properties[] = $this->getSeparator();

        if ($this->isSortable()) {
            $properties[] = 'SORTABLE';
        }

        if ($this->isNoindex()) {
            $properties[] = 'NOINDEX';
        }

        return $properties;
    }
}
