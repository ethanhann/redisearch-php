<?php

namespace Eeh\Redisearch\Fields;

class FieldFactory
{
    public static function make($name, $value)
    {
        if (is_string($value)) {
            return new TextField($name, $value);
        }
        if (is_numeric($value)) {
            return new NumericField($name, $value);
        }
        return new GeoField($name, $value);
    }
}