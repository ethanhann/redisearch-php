<?php

namespace Eeh\Redisearch\Fields;

class NumericField extends AbstractField
{
    public function getType(): string
    {
        return 'NUMERIC';
    }
}
