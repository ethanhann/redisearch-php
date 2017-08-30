<?php

namespace Ehann\Tests\RediSearch\Document;

use Ehann\RediSearch\Document\Document;
use Ehann\RediSearch\Fields\FieldFactory;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testShouldGetDefinition()
    {
        $expectedNumberOfElements = 3;
        $expectedScore = 1.0;
        $subject = new Document();

        $definition = $subject->getDefinition();

        $this->assertCount($expectedNumberOfElements, $definition);
        $this->assertNotEmpty($definition[0]);
        $this->assertEquals($expectedScore, $definition[1]);
        $this->assertEquals('FIELDS', $definition[2]);
    }

    public function testShouldGetDefinitionWithOptions()
    {
        $expectedNumberOfElements = 11;
        $expectedPayload = 'foo';
        $isNoSave = true;
        $shouldReplace = true;
        $expectedId = '9999';
        $expectedScore = 1.2;
        $expectedLanguage = 'EN';
        $expectedFieldName = 'field name';
        $expectedFieldValue = 'field value';
        $subject = (new Document())
            ->setNoSave($isNoSave)
            ->setId($expectedId)
            ->setLanguage($expectedLanguage)
            ->setPayload($expectedPayload)
            ->setReplace($shouldReplace)
            ->setScore($expectedScore);
        $subject->customField = FieldFactory::make($expectedFieldName, $expectedFieldValue);

        $definition = $subject->getDefinition();

        $this->assertCount($expectedNumberOfElements, $definition);
        $this->assertEquals($expectedId, $definition[0]);
        $this->assertEquals($expectedScore, $definition[1]);
        $this->assertEquals('NOSAVE', $definition[2]);
        $this->assertEquals('REPLACE', $definition[3]);
        $this->assertEquals('LANGUAGE', $definition[4]);
        $this->assertEquals($expectedLanguage, $definition[5]);
        $this->assertEquals('PAYLOAD', $definition[6]);
        $this->assertEquals($expectedPayload, $definition[7]);
        $this->assertEquals('FIELDS', $definition[8]);
        $this->assertEquals($expectedFieldName, $definition[9]);
        $this->assertEquals($expectedFieldValue, $definition[10]);
    }
}
