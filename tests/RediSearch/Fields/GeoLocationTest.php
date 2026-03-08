<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\GeoLocation;
use PHPUnit\Framework\TestCase;

class GeoLocationTest extends TestCase
{
    public function testShouldGetStringValueOfGeoLocation(): void
    {
        // Arrange
        $expected = '50.9741 20.1415';

        // Act
        $actual = (string)(new GeoLocation(50.9741, 20.1415));

        // Assert
        $this->assertSame($expected, $actual);
    }
}
