<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Fields\GeoLocation;
use Ehann\RediSearch\Query\Builder;
use Ehann\Tests\Stubs\TestIndex;
use Ehann\Tests\AbstractTestCase;

class BuilderTest extends AbstractTestCase
{
    /** @var Builder */
    private $subject;
    private $expectedResult1;
    private $expectedResult2;
    private $expectedResult3;
    public function setUp()
    {
        $this->indexName = 'QueryBuilderTest';
        $index = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title')
            ->addTextField('author')
            ->addNumericField('price')
            ->addNumericField('stock')
            ->addGeoField('location');
        $index->create();
        $index->makeDocument();
        $this->expectedResult1 = [
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
            'location' => new GeoLocation(10.9190500, 52.0504100),
        ];
        $index->add($this->expectedResult1);
        $this->expectedResult2 = [
            'title' => 'Shoes in the 22st Century',
            'author' => 'Jessica',
            'price' => 18.85,
            'stock' => 32,
            'location' => new GeoLocation(50.9190500, 4.0504100),
        ];
        $index->add($this->expectedResult2);
        $this->expectedResult3 = [
            'title' => 'How to be awesome, part 2, section 13, appendix A',
            'author' => 'Jack',
            'price' => 18.95,
            'stock' => 11,
            'location' => new GeoLocation(10.9190500, 52.0504100),
        ];
        $index->add($this->expectedResult3);
        $this->subject = (new Builder($this->redisClient, $this->indexName));
    }

    public function tearDown()
    {
        $this->redisClient->flushAll();
    }

    public function testSearch()
    {
        $result = $this->subject->search('Shoes');

        $this->assertTrue($result->getCount() === 1);
    }

    public function testSearchWithScores()
    {
        $result = $this->subject->withScores()->search('Shoes');

        $this->assertTrue($result->getCount() === 1);
        $this->assertTrue(property_exists($result->getDocuments()[0], 'score'));
    }

    public function testSearchWithPayloads()
    {
        $result = $this->subject->withPayloads()->search('Shoes');

        $this->assertEquals(1, $result->getCount());
        $this->assertTrue(property_exists($result->getDocuments()[0], 'payload'));
    }

    public function testVerbatimSearch()
    {
        $result = $this->subject->verbatim()->search('Shoes in the 22st Century');

        $this->assertEquals(1, $result->getCount());
    }

    public function testVerbatimSearchFails()
    {
        $result = $this->subject->verbatim()->search('Shoess');

        $this->assertEquals(0, $result->getCount());
    }

    public function testGeoQuery()
    {
        $expectedCount = 1;

        $result = $this->subject
            ->geoFilter('location', '51.0544782', '3.7178716', '100', 'km')
            ->search('Shoes');

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($this->expectedResult2['author'], $result->getDocuments()[0]->author);
    }

    public function testLimitSearch()
    {
        $expectedCount = 1;

        $result = $this->subject->limit(0, $expectedCount)->search('How');

        $this->assertCount($expectedCount, $result->getDocuments());
    }

    public function testSearchWithNoContent()
    {
        $result = $this->subject->noContent()->search('How');

        $this->assertFalse(property_exists($result->getDocuments()[0], 'title'));
        $this->assertFalse(property_exists($result->getDocuments()[1], 'title'));
    }

    public function testSearchWithDefaultSlop()
    {
        $result = $this->subject->slop(0)->search('How appendix');

        $this->assertCount(0, $result->getDocuments());
    }

    public function testSearchWithNonDefaultSlop()
    {
        $result = $this->subject->slop(10)->search('How awesome');

        $this->assertCount(2, $result->getDocuments());
    }
}
