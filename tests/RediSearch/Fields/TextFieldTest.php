<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\TextField;
use PHPUnit\Framework\TestCase;

class TextFieldTest extends TestCase
{
    private TextField $subject;
    private string $fieldName = 'MyTextField';
    private string $fieldType = 'TEXT';
    private string $weightKeyword = 'WEIGHT';
    private float $defaultWeight = 1.0;

    public function setUp(): void
    {
        $this->subject = new TextField($this->fieldName);
    }

    public function testShouldGetCorrectType(): void
    {
        // Arrange — see setUp()

        // Act
        $type = $this->subject->getType();

        // Assert
        $this->assertSame($this->fieldType, $type);
    }

    public function testShouldGetWeight(): void
    {
        // Arrange — see setUp()

        // Act
        $weight = $this->subject->getWeight();

        // Assert
        $this->assertSame($this->defaultWeight, $weight);
    }

    public function testShouldSetWeight(): void
    {
        // Arrange
        $expected = 243.0;

        // Act
        $weight = $this->subject->setWeight($expected)->getWeight();

        // Assert
        $this->assertSame($expected, $weight);
    }

    public function testShouldGetTypeDefinition(): void
    {
        // Arrange — see setUp()

        // Act
        $typeDefinition = $this->subject->getTypeDefinition();

        // Assert
        $this->assertSame($this->fieldName, $typeDefinition[0]);
        $this->assertSame($this->fieldType, $typeDefinition[1]);
        $this->assertSame($this->weightKeyword, $typeDefinition[2]);
        $this->assertSame($this->defaultWeight, $typeDefinition[3]);
    }
}
