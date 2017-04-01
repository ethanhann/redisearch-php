<?php

namespace Ehann\RediSearch\Fields;

class GeoField extends AbstractField
{
    public function getType(): string
    {
        return 'GEO';
    }
}
