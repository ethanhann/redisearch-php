<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Suggestion;
use Ehann\Tests\RediSearchTestCase;

class SuggestionTest extends RediSearchTestCase
{
    /** @var Suggestion */
    private $subject;

    public function setUp(): void
    {
        $this->subject = (new Suggestion($this->redisClient, 'foo'));
    }

    public function tearDown(): void
    {
        $this->redisClient->flushAll();
    }

    public function testShouldAddSuggestion()
    {
        $expectedSizeOfIndex = 1;

        $result = $this->subject->add('bar', 9.23);

        $this->assertEquals($expectedSizeOfIndex, $result);
    }

    public function testShouldIncrementExistingSuggestion()
    {
        $expectedSizeOfIndex = 2;
        $expectedFirstResult = 'bar';
        $expectedSecondResult = 'baz';
        $this->subject->add($expectedFirstResult, 5);
        $this->subject->add($expectedSecondResult, 7);

        $result = $this->subject->add($expectedFirstResult, 10, true);

        $actualSuggestion = $this->subject->get('ba');
        $this->assertEquals($expectedSizeOfIndex, $result);
        $this->assertEquals($expectedFirstResult, $actualSuggestion[0]);
        $this->assertEquals($expectedSecondResult, $actualSuggestion[1]);
    }

    public function testShouldDeleteSuggestion()
    {
        $string = 'bar';
        $this->subject->add($string, 9.23);

        $result = $this->subject->delete($string);

        $this->assertTrue($result);
    }

    public function testShouldGetDictionaryLength()
    {
        $this->subject->add('bar', 9.23);
        $this->subject->add('baz', 4.99);
        $this->subject->add('qux', 14.0);
        $expectedSizeOfIndex = 3;

        $result = $this->subject->length();

        $this->assertEquals($expectedSizeOfIndex, $result);
    }

    public function testShouldGetSuggestion()
    {
        $expectedFirstResult = 'baz';
        $expectedSecondResult = 'bar';
        $this->subject->add('bar', 1.23);
        $this->subject->add('baz', 24.99);
        $this->subject->add('qux', 14.0);
        $expectedSizeOfResults = 2;

        $result = $this->subject->get('ba');

        $this->assertCount($expectedSizeOfResults, $result);
        $this->assertEquals($expectedFirstResult, $result[0]);
        $this->assertEquals($expectedSecondResult, $result[1]);
    }
}
