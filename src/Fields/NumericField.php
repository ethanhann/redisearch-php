<?php

namespace Ehann\RediSearch\Fields;

class NumericField extends AbstractField
{
    public function getType(): string
    {
        return 'NUMERIC';
    }
}
