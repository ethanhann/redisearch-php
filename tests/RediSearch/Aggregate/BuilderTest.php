<?php

namespace Ehann\Tests\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Builder;
use Ehann\RediSearch\Aggregate\Reducers\Avg;
use Ehann\Tests\Stubs\TestIndex;
use Ehann\Tests\RediSearchTestCase;

/**
 * @group aggregate
 */
class BuilderTest extends RediSearchTestCase
{
    private Builder $subject;
    private array $expectedResult1;
    private array $expectedResult2;
    private array $expectedResult3;
    private array $expectedResult4;

    public function setUp(): void
    {
        parent::setUp();
        $this->indexName = 'AggregateBuilderTest';
        $index = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title', 1.0, true)
            ->addTextField('author', true)
            ->addNumericField('price', true)
            ->addNumericField('stock', true);
        $index->create();

        $this->expectedResult1 = [
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ];
        $index->add($this->expectedResult1);
        $this->expectedResult2 = [
            'title' => 'How to be awesome, part 2 - Electric Boogaloo',
            'author' => 'Jessica',
            'price' => 18.85,
            'stock' => 32,
        ];
        $index->add($this->expectedResult2);
        $this->expectedResult3 = [
            'title' => 'How to be awesome, part 3, section 13, appendix A',
            'author' => 'Jack',
            'price' => 38.85,
            'stock' => 32,
        ];
        $index->add($this->expectedResult3);
        $this->expectedResult4 = [
            'title' => 'How to be awesome.',
            'author' => 'Barry',
            'price' => 19.99,
            'stock' => 231,
        ];
        $index->add($this->expectedResult4);
        $this->expectedResult4 = [
            'title' => 'How to be awesome.',
            'author' => 'Lizzy',
            'price' => 14.99,
            'stock' => 10,
        ];
        $index->add($this->expectedResult4);
        $this->subject = (new Builder($this->redisClient, $this->indexName));
    }

    public function tearDown(): void
    {
        $this->redisClient->flushAll();
    }

    public function testGetAverageOfNumeric(): void
    {
        // Arrange
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->avg('price')
            ->search();

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

    public function testGetAggregationAsArray(): void
    {
        // Arrange
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->avg('price')
            ->search('*', true);

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]['avg_price']);
    }

    public function testGetGroupByAndReduce(): void
    {
        // Arrange
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->reduce(new Avg('price'))
            ->search();

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

    public function testGetGroupByAndReduceAndFilter(): void
    {
        // Arrange
        $expectedCount = 2;
        $expectedAveragePrice = 18.85;

        // Act
        $result = $this->subject
            ->groupBy('author')
            ->reduce(new Avg('price'))
            ->filter('@author == "Jessica" || @author == "Jack"')
            ->search();

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

    public function testPipelineHasCommands(): void
    {
        // Arrange
        $this->subject
            ->groupBy('title')
            ->avg('price');
        $this->subject->limit(0, 10);

        // Act
        $result = $this->subject->getPipeline();

        // Assert
        $this->assertNotEmpty($result, 'Pipeline has no commands.');
    }

    public function testClearPipeline(): void
    {
        // Arrange
        $this->subject
            ->groupBy('title')
            ->avg('price');

        // Act
        $this->subject->clear();

        // Assert
        $this->assertEmpty($this->subject->getPipeline(), 'Failed to clear pipeline.');
    }

    public function testGetCount(): void
    {
        // Arrange
        $expected1 = 3;
        $expected2 = 1;
        $expected3 = 1;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->count(0)
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->count);
        $this->assertSame($expected2, $result->getDocuments()[1]->count);
        $this->assertSame($expected3, $result->getDocuments()[2]->count);
    }

    public function testGetCountDistinct(): void
    {
        // Arrange
        $expected1 = 1;
        $expected2 = 1;
        $expected3 = 1;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->countDistinct('title')
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->count_distinct_title);
        $this->assertSame($expected2, $result->getDocuments()[1]->count_distinct_title);
        $this->assertSame($expected3, $result->getDocuments()[2]->count_distinct_title);
    }

    public function testGetCountDistinctWithReduceByField(): void
    {
        // Arrange
        $expected1 = 2;
        $expected2 = 1;

        // Act
        $result = $this->subject
            ->groupBy('stock')
            ->countDistinct('title')
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->count_distinct_title);
        $this->assertSame($expected2, $result->getDocuments()[1]->count_distinct_title);
    }

    public function testGetCountDistinctApproximate(): void
    {
        // Arrange
        $expected1 = 1;
        $expected2 = 1;
        $expected3 = 1;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->countDistinctApproximate('title')
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->count_distinctish_title);
        $this->assertSame($expected2, $result->getDocuments()[1]->count_distinctish_title);
        $this->assertSame($expected3, $result->getDocuments()[2]->count_distinctish_title);
    }

    public function testGetSum(): void
    {
        // Arrange
        $expected1 = 472;
        $expected2 = 32;
        $expected3 = 32;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->sum('stock')
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->sum_stock);
        $this->assertSame($expected2, $result->getDocuments()[1]->sum_stock);
        $this->assertSame($expected3, $result->getDocuments()[2]->sum_stock);
    }

    public function testGetMax(): void
    {
        // Arrange
        $expected1 = 19.99;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->max('price')
            ->search();

        // Assert
        $this->assertEquals($expected1, $result->getDocuments()[0]->max_price);
    }

    public function testGetMin(): void
    {
        // Arrange
        $expected1 = 9.99;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->min('price')
            ->search();

        // Assert
        $this->assertEquals($expected1, $result->getDocuments()[0]->min_price);
    }

    public function testGetAbsoluteMin(): void
    {
        // Arrange
        $expected = 9.99;

        // Act
        $result = $this->subject
            ->groupBy()
            ->min('price')
            ->search();

        // Assert
        $this->assertEquals($expected, $result->getDocuments()[0]->min_price);
    }

    public function testGetAbsoluteMax(): void
    {
        // Arrange
        $expected = 38.85;

        // Act
        $result = $this->subject
            ->groupBy()
            ->max('price')
            ->search();

        // Assert
        $this->assertEquals($expected, $result->getDocuments()[0]->max_price);
    }

    public function testGetQuantile(): void
    {
        // Arrange
        $expected1 = 19.99;
        $expected2 = 18.85;
        $expected3 = 38.85;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->quantile('price', 1)
            ->search();

        // Assert
        $documents = $result->getDocuments();
        $this->assertEquals($expected1, $documents[0]->quantile_price);
        $this->assertEquals($expected2, $documents[1]->quantile_price);
        $this->assertEquals($expected3, $documents[2]->quantile_price);
    }

    public function testGetAbsoluteQuantile(): void
    {
        // Arrange
        $expected = 18.85;

        // Act
        $result = $this->subject
            ->groupBy()
            ->quantile('price', 0.5)
            ->search();

        // Assert
        $this->assertEquals($expected, $result->getDocuments()[0]->quantile_price);
    }

    public function testGetStandardDeviation(): void
    {
        // Arrange
        $expected = 5;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->standardDeviation('price')
            ->search();

        // Assert
        $this->assertEquals($expected, $result->getDocuments()[0]->stddev_price);
    }

    public function testSortByAscending(): void
    {
        // Arrange
        $expected1 = 'how to be awesome, part 2 - electric boogaloo';
        $expected2 = 'how to be awesome, part 3, section 13, appendix a';
        $expected3 = 'how to be awesome.';

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->sortBy('title')
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->title);
        $this->assertSame($expected2, $result->getDocuments()[1]->title);
        $this->assertSame($expected3, $result->getDocuments()[2]->title);
    }

    public function testSortByDescending(): void
    {
        // Arrange
        $expected1 = 'how to be awesome.';
        $expected2 = 'how to be awesome, part 3, section 13, appendix a';
        $expected3 = 'how to be awesome, part 2 - electric boogaloo';

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->sortBy('title', false)
            ->search();

        // Assert
        $this->assertSame($expected1, $result->getDocuments()[0]->title);
        $this->assertSame($expected2, $result->getDocuments()[1]->title);
        $this->assertSame($expected3, $result->getDocuments()[2]->title);
    }

    public function testSortByWithMax(): void
    {
        // Arrange
        $expected = 1;

        // Act
        $result = $this->subject
            ->groupBy('title')
            ->sortBy('title', true, $expected)
            ->search();

        // Assert
        $this->assertSame($expected, $result->getCount());
    }
}
