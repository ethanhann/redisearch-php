<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\FieldFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FieldFactoryTest extends TestCase
{
    public function testShouldThrowWhenFieldTypeIsUnknown(): void
    {
        // Arrange
        $unknownType = 'SOME_NON_EXISTING_TYPE';

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        FieldFactory::make($unknownType, new \stdClass());
    }
}
