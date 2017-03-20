<?php

namespace Ehann\RediSearch\Fields;

class GeoLocation
{
    protected $name;
    protected $longitude;
    protected $latitude;

    public function __construct(float $longitude, float $latitude)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
    }

    public function __toString()
    {
        return "{$this->longitude} {$this->latitude}";
    }
}
