<?php

namespace Ehann\Tests\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\AggregationResult;
use Ehann\Tests\RediSearchTestCase;

#[PHPUnit\Framework\Attributes\Group('aggregate')]
class AggregationResultTest extends RediSearchTestCase
{
    protected AggregationResult $subject;
    protected array $expectedDocuments;

    public function setUp(): void
    {
        $this->expectedDocuments = [
            ['title' => 'part1'],
            ['title' => 'part2'],
        ];
        $this->subject = new AggregationResult(count($this->expectedDocuments), $this->expectedDocuments);
    }

    public function testGetCount(): void
    {
        // Arrange
        $expected = count($this->expectedDocuments);

        // Act
        $result = $this->subject->getCount();

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testGetDocuments(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->getDocuments();

        // Assert
        $this->assertSame($this->expectedDocuments, $result);
    }

    public function testMakeAggregationResultWithInvalidRedisResult(): void
    {
        // Arrange — no result data, invalid Redis response

        // Act
        $result = AggregationResult::makeAggregationResult([], false);

        // Assert
        $this->assertFalse($result);
    }
}
