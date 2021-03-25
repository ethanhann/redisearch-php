<?php

namespace Ehann\Tests\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\AggregationResult;
use Ehann\Tests\RediSearchTestCase;

/**
 * @group aggregate
 */
class AggregationResultTest extends RediSearchTestCase
{
    /** @var AggregationResult */
    protected $subject;
    protected $expectedDocuments;

    public function setUp(): void
    {
        $this->expectedDocuments = [
            ['title' => 'part1'],
            ['title' => 'part2'],
        ];
        $this->subject = new AggregationResult(count($this->expectedDocuments), $this->expectedDocuments);
    }

    public function testGetCount()
    {
        $expected = count($this->expectedDocuments);

        $result = $this->subject->getCount();

        $this->assertEquals($expected, $result);
    }

    public function testGetDocuments()
    {
        $result = $this->subject->getDocuments();

        $this->assertEquals($this->expectedDocuments, $result);
    }

    public function testMakeAggregationResultWithInvalidRedisResult()
    {
        $result = AggregationResult::makeAggregationResult([], false);

        $this->assertFalse($result);
    }
}
