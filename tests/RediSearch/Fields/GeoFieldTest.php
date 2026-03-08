<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\GeoField;
use PHPUnit\Framework\TestCase;

class GeoFieldTest extends TestCase
{
    public function testShouldGetCorrectType(): void
    {
        // Arrange
        $expected = 'GEO';

        // Act
        $type = (new GeoField('MyGeoField'))->getType();

        // Assert
        $this->assertSame($expected, $type);
    }
}
