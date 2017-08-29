<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\TextField;
use PHPUnit\Framework\TestCase;

class TextFieldTest extends TestCase
{
    /** @var TextField */
    private $subject;
    /** @var string */
    private $fieldName = 'MyTextField';
    /** @var string */
    private $fieldType = 'TEXT';
    /** @var string */
    private $weightKeyword = 'WEIGHT';
    /** @var float */
    private $defaultWeight = 1.0;

    public function setUp()
    {
        $this->subject = new TextField($this->fieldName);
    }

    public function testShouldGetCorrectType()
    {
        $type = $this->subject->getType();

        $this->assertEquals($this->fieldType, $type);
    }

    public function testShouldGetWeight()
    {
        $weight = $this->subject->getWeight();

        $this->assertEquals($this->defaultWeight, $weight);
    }

    public function testShouldSetWeight()
    {
        $expected = 243.0;

        $weight = $this->subject
            ->setWeight($expected)
            ->getWeight();

        $this->assertEquals($expected, $weight);
    }

    public function testShouldGetTypeDefinition()
    {
        $typeDefinition = $this->subject->getTypeDefinition();

        $this->assertEquals($this->fieldName, $typeDefinition[0]);
        $this->assertEquals($this->fieldType, $typeDefinition[1]);
        $this->assertEquals($this->weightKeyword, $typeDefinition[2]);
        $this->assertEquals($this->defaultWeight, $typeDefinition[3]);
    }
}
