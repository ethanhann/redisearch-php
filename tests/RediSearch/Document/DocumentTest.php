<?php

namespace Ehann\Tests\RediSearch\Document;

use Ehann\RediSearch\Document\Document;
use Ehann\RediSearch\Exceptions\OutOfRangeDocumentScoreException;
use Ehann\RediSearch\Fields\FieldFactory;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testShouldGetDefinition(): void
    {
        // Arrange
        $expectedNumberOfElements = 3;
        $expectedScore = 1.0;
        $subject = new Document();

        // Act
        $definition = $subject->getDefinition();

        // Assert
        $this->assertCount($expectedNumberOfElements, $definition);
        $this->assertNotEmpty($definition[0]);
        $this->assertSame($expectedScore, $definition[1]);
        $this->assertSame('FIELDS', $definition[2]);
    }

    public function testShouldGetDefinitionWithOptions(): void
    {
        // Arrange
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

        // Act
        $definition = $subject->getDefinition();

        // Assert
        $this->assertCount($expectedNumberOfElements, $definition);
        $this->assertSame($expectedId, $definition[0]);
        $this->assertSame($expectedScore, $definition[1]);
        $this->assertSame('NOSAVE', $definition[2]);
        $this->assertSame('REPLACE', $definition[3]);
        $this->assertSame('PARTIAL', $definition[4]);
        $this->assertSame('NOCREATE', $definition[5]);
        $this->assertSame('LANGUAGE', $definition[6]);
        $this->assertSame($expectedLanguage, $definition[7]);
        $this->assertSame('PAYLOAD', $definition[8]);
        $this->assertSame($expectedPayload, $definition[9]);
        $this->assertSame('FIELDS', $definition[10]);
        $this->assertSame($expectedFieldName, $definition[11]);
        $this->assertSame($expectedFieldValue, $definition[12]);
    }

    public function testShouldThrowExceptionWhenScoreIsTooLow(): void
    {
        // Arrange
        $subject = new Document();

        // Assert
        $this->expectException(OutOfRangeDocumentScoreException::class);

        // Act
        $subject->setScore(-0.1);
    }

    public function testShouldThrowExceptionWhenScoreIsTooHigh(): void
    {
        // Arrange
        $subject = new Document();

        // Assert
        $this->expectException(OutOfRangeDocumentScoreException::class);

        // Act
        $subject->setScore(1.1);
    }
}
