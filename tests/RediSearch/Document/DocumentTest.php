<?php

namespace Ehann\Tests\RediSearch\Document;

use Ehann\RediSearch\Document\Document;
use Ehann\RediSearch\Exceptions\OutOfRangeDocumentScoreException;
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
        $expectedNumberOfElements = 13;
        $expectedPayload = 'foo';
        $isNoSave = true;
        $shouldReplace = true;
        $shouldPartial = true;
        $shouldNoCreate = true;
        $expectedId = '9999';
        $expectedScore = 0.2;
        $expectedLanguage = 'EN';
        $expectedFieldName = 'field name';
        $expectedFieldValue = 'field value';
        $subject = (new Document())
            ->setNoSave($isNoSave)
            ->setId($expectedId)
            ->setLanguage($expectedLanguage)
            ->setPayload($expectedPayload)
            ->setReplace($shouldReplace)
            ->setPartial($shouldPartial)
            ->setNoCreate($shouldNoCreate)
            ->setScore($expectedScore);
        $subject->customField = FieldFactory::make($expectedFieldName, $expectedFieldValue);

        $definition = $subject->getDefinition();

        $this->assertCount($expectedNumberOfElements, $definition);
        $this->assertEquals($expectedId, $definition[0]);
        $this->assertEquals($expectedScore, $definition[1]);
        $this->assertEquals('NOSAVE', $definition[2]);
        $this->assertEquals('REPLACE', $definition[3]);
        $this->assertEquals('PARTIAL', $definition[4]);
        $this->assertEquals('NOCREATE', $definition[5]);
        $this->assertEquals('LANGUAGE', $definition[6]);
        $this->assertEquals($expectedLanguage, $definition[7]);
        $this->assertEquals('PAYLOAD', $definition[8]);
        $this->assertEquals($expectedPayload, $definition[9]);
        $this->assertEquals('FIELDS', $definition[10]);
        $this->assertEquals($expectedFieldName, $definition[11]);
        $this->assertEquals($expectedFieldValue, $definition[12]);
    }

    public function testShouldThrowExceptionWhenScoreIsTooLow()
    {
        $this->expectException(OutOfRangeDocumentScoreException::class);
        $subject = new Document();

        $subject->setScore(-0.1);
    }

    public function testShouldThrowExceptionWhenScoreIsTooHigh()
    {
        $this->expectException(OutOfRangeDocumentScoreException::class);
        $subject = new Document();

        $subject->setScore(1.1);
    }
}
