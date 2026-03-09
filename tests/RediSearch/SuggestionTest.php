<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Suggestion;
use Ehann\Tests\RediSearchTestCase;

class SuggestionTest extends RediSearchTestCase
{
    private Suggestion $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = (new Suggestion($this->redisClient, 'foo'));
    }

    public function tearDown(): void
    {
        $this->redisClient->flushAll();
    }

    public function testShouldAddSuggestion(): void
    {
        // Arrange
        $expectedSizeOfIndex = 1;

        // Act
        $result = $this->subject->add('bar', 9.23);

        // Assert
        $this->assertSame($expectedSizeOfIndex, $result);
    }

    public function testShouldIncrementExistingSuggestion(): void
    {
        // Arrange
        $expectedSizeOfIndex = 2;
        $expectedFirstResult = 'bar';
        $expectedSecondResult = 'baz';
        $this->subject->add($expectedFirstResult, 5);
        $this->subject->add($expectedSecondResult, 7);

        // Act
        $result = $this->subject->add($expectedFirstResult, 10, true);

        // Assert
        $actualSuggestion = $this->subject->get('ba');
        $this->assertSame($expectedSizeOfIndex, $result);
        $this->assertSame($expectedFirstResult, $actualSuggestion[0]);
        $this->assertSame($expectedSecondResult, $actualSuggestion[1]);
    }

    public function testShouldDeleteSuggestion(): void
    {
        // Arrange
        $string = 'bar';
        $this->subject->add($string, 9.23);

        // Act
        $result = $this->subject->delete($string);

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldGetDictionaryLength(): void
    {
        // Arrange
        $this->subject->add('bar', 9.23);
        $this->subject->add('baz', 4.99);
        $this->subject->add('qux', 14.0);
        $expectedSizeOfIndex = 3;

        // Act
        $result = $this->subject->length();

        // Assert
        $this->assertSame($expectedSizeOfIndex, $result);
    }

    public function testShouldGetSuggestion(): void
    {
        // Arrange
        $expectedFirstResult = 'baz';
        $expectedSecondResult = 'bar';
        $this->subject->add('bar', 1.23);
        $this->subject->add('baz', 24.99);
        $this->subject->add('qux', 14.0);
        $expectedSizeOfResults = 2;

        // Act
        $result = $this->subject->get('ba');

        // Assert
        $this->assertCount($expectedSizeOfResults, $result);
        $this->assertSame($expectedFirstResult, $result[0]);
        $this->assertSame($expectedSecondResult, $result[1]);
    }

    public function testShouldGetSuggestionWithScore(): void
    {
        // Arrange
        $expectedSuggestion = 'bar';
        $expectedScore = '2147483648';
        $this->subject->add('bar', 1.23);
        $this->subject->add('baz', 24.99);
        $this->subject->add('qux', 14.0);
        $expectedSizeOfResults = 2;

        // Act
        $result = $this->subject->get('bar', false, false, 1, true);

        // Assert
        $this->assertCount($expectedSizeOfResults, $result);
        $this->assertSame($expectedSuggestion, $result[0]);
        $this->assertSame($expectedScore, $result[1]);
    }
}
