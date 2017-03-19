<?php

namespace Eeh\RediSearch\Fields;

class GeoField extends AbstractField
{
    public function getType(): string
    {
        return 'GEO';
    }
}
