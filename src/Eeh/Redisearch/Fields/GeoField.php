<?php

namespace Eeh\Redisearch\Fields;

class GeoField extends AbstractField
{
    public function getType(): string
    {
        return 'GEO';
    }
}
