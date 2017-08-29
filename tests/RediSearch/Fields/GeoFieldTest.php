<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Fields\GeoField;
use PHPUnit\Framework\TestCase;

class GeoFieldTest extends TestCase
{
    public function testShouldGetCorrectType()
    {
        $expected = 'GEO';

        $type = (new GeoField('MyGeoField'))->getType();

        $this->assertEquals($expected, $type);
    }
}