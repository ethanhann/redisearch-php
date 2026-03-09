<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\NumericField;
use PHPUnit\Framework\TestCase;

class NumericFieldTest extends TestCase
{
    public function testShouldGetCorrectType(): void
    {
        // Arrange
        $expected = 'NUMERIC';

        // Act
        $type = (new NumericField('MyNumericField'))->getType();

        // Assert
        $this->assertSame($expected, $type);
    }
}
