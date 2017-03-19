<?php

namespace Eeh\RediSearch\Fields;

class NumericField extends AbstractField
{
    public function getType(): string
    {
        return 'NUMERIC';
    }
}
