<?php

namespace Ehann\RediSearch\Fields;

class GeoField extends AbstractField
{
    use Noindex;

    public function getType(): string
    {
        return 'GEO';
    }

    public function getTypeDefinition(): array
    {
        $properties = parent::getTypeDefinition();
        if ($this->isNoindex()) {
            $properties[] = 'NOINDEX';
        }

        return $properties;
    }
}
