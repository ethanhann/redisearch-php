<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\GeoLocation;
use PHPUnit\Framework\TestCase;

class GeoLocationTest extends TestCase
{
    public function testShouldGetStringValueOfGeoLocation()
    {
        $expected = '50.9741 20.1415';

        $actual = (string)(new GeoLocation(50.9741, 20.1415));

        $this->assertEquals($expected, $actual);
    }
}
