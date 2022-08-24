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
    /** @var Builder */
    private $subject;
    private $expectedResult1;
    private $expectedResult2;
    private $expectedResult3;
    private $expectedResult4;

    public function setUp(): void
    {
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

    public function testGetAverageOfNumeric()
    {
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        $result = $this->subject
            ->groupBy('title')
            ->avg('price')
            ->search();

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

    public function testGetAggregationAsArray()
    {
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        $result = $this->subject
            ->groupBy('title')
            ->avg('price')
            ->search('*', true);

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]['avg_price']);
    }

    public function testGetGroupByAndReduce()
    {
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        $result = $this->subject
            ->groupBy('title')
            ->reduce(new Avg('price'))
            ->search();

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

    public function testGetGroupByAndReduceAndFilter()
    {
        $expectedCount = 2;
        $expectedAveragePrice = 18.85;

        $result = $this->subject
            ->groupBy('author')
            ->reduce(new Avg('price'))
            ->filter('@author == "Jessica" || @author == "Jack"')
            ->search();

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

    public function testPipelineHasCommands()
    {
        $this->subject
            ->groupBy('title')
            ->avg('price');
        $this->subject->limit(0, 10);

        $result = $this->subject->getPipeline();

        $this->assertNotEmpty($result, 'Pipeline has no commands.');
    }

    public function testClearPipeline()
    {
        $this->subject
            ->groupBy('title')
            ->avg('price');

        $this->subject->clear();

        $this->assertEmpty($this->subject->getPipeline(), 'Failed to clear pipeline.');
    }

    public function testGetCount()
    {
        $expected1 = 3;
        $expected2 = 1;
        $expected3 = 1;

        $result = $this->subject
            ->groupBy('title')
            ->count(0)
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->count);
        $this->assertEquals($expected2, $result->getDocuments()[1]->count);
        $this->assertEquals($expected3, $result->getDocuments()[2]->count);
    }

    public function testGetCountDistinct()
    {
        $expected1 = 1;
        $expected2 = 1;
        $expected3 = 1;

        $result = $this->subject
            ->groupBy('title')
            ->countDistinct('title')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->count_distinct_title);
        $this->assertEquals($expected2, $result->getDocuments()[1]->count_distinct_title);
        $this->assertEquals($expected3, $result->getDocuments()[2]->count_distinct_title);
    }

    public function testGetCountDistinctWithReduceByField()
    {
        $expected1 = 2;
        $expected2 = 1;

        $result = $this->subject
            ->groupBy('stock')
            ->countDistinct('title')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->count_distinct_title);
        $this->assertEquals($expected2, $result->getDocuments()[1]->count_distinct_title);
    }

    public function testGetCountDistinctApproximate()
    {
        $expected1 = 1;
        $expected2 = 1;
        $expected3 = 1;

        $result = $this->subject
            ->groupBy('title')
            ->countDistinctApproximate('title')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->count_distinctish_title);
        $this->assertEquals($expected2, $result->getDocuments()[1]->count_distinctish_title);
        $this->assertEquals($expected3, $result->getDocuments()[2]->count_distinctish_title);
    }

    public function testGetSum()
    {
        $expected1 = 472;
        $expected2 = 32;
        $expected3 = 32;

        $result = $this->subject
            ->groupBy('title')
            ->sum('stock')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->sum_stock);
        $this->assertEquals($expected2, $result->getDocuments()[1]->sum_stock);
        $this->assertEquals($expected3, $result->getDocuments()[2]->sum_stock);
    }

    public function testGetMax()
    {
        $expected1 = 19.99;

        $result = $this->subject
            ->groupBy('title')
            ->max('price')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->max_price);
    }

    public function testGetMin()
    {
        $expected1 = 9.99;

        $result = $this->subject
            ->groupBy('title')
            ->min('price')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->min_price);
    }

    public function testGetAbsoluteMin()
    {
        $expected = 9.99;

        $result = $this->subject
            ->groupBy()
            ->min('price')
            ->search();

        $this->assertEquals($expected, $result->getDocuments()[0]->min_price);
    }

    public function testGetAbsoluteMax()
    {
        $expected = 38.85;

        $result = $this->subject
            ->groupBy()
            ->max('price')
            ->search();

        $this->assertEquals($expected, $result->getDocuments()[0]->max_price);
    }

    public function testGetQuantile()
    {
        $expected1 = 19.99;
        $expected2 = 18.85;
        $expected3 = 38.85;

        $result = $this->subject
            ->groupBy('title')
            ->quantile('price', 1)
            ->search();

        $documents = $result->getDocuments();
        $this->assertEquals($expected1, $documents[0]->quantile_price);
        $this->assertEquals($expected2, $documents[1]->quantile_price);
        $this->assertEquals($expected3, $documents[2]->quantile_price);
    }

    public function testGetAbsoluteQuantile()
    {
        $expected = 18.85;

        $result = $this->subject
            ->groupBy()
            ->quantile('price', 0.5)
            ->search();

        $this->assertEquals($expected, $result->getDocuments()[0]->quantile_price);
    }

    public function testGetStandardDeviation()
    {
        $expected = 5;

        $result = $this->subject
            ->groupBy('title')
            ->standardDeviation('price')
            ->search();

        $this->assertEquals($expected, $result->getDocuments()[0]->stddev_price);
    }

    public function testSortByAscending()
    {
        $expected1 = 'how to be awesome, part 2 - electric boogaloo';
        $expected2 = 'how to be awesome, part 3, section 13, appendix a';
        $expected3 = 'how to be awesome.';

        $result = $this->subject
            ->groupBy('title')
            ->sortBy('title')
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->title);
        $this->assertEquals($expected2, $result->getDocuments()[1]->title);
        $this->assertEquals($expected3, $result->getDocuments()[2]->title);
    }

    public function testSortByDescending()
    {
        $expected1 = 'how to be awesome.';
        $expected2 = 'how to be awesome, part 3, section 13, appendix a';
        $expected3 = 'how to be awesome, part 2 - electric boogaloo';

        $result = $this->subject
            ->groupBy('title')
            ->sortBy('title', false)
            ->search();

        $this->assertEquals($expected1, $result->getDocuments()[0]->title);
        $this->assertEquals($expected2, $result->getDocuments()[1]->title);
        $this->assertEquals($expected3, $result->getDocuments()[2]->title);
    }

    public function testSortByWithMax()
    {
        $expected = 1;

        $result = $this->subject
            ->groupBy('title')
            ->sortBy('title', true, $expected)
            ->search();

        $this->assertEquals($expected, $result->getCount());
    }
}
