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
        $this->subject = (new Builder($this->redisClient, $this->indexName));
    }

    public function tearDown()
    {
        $this->redisClient->flushAll();
    }

    public function testSearch()
    {
        $result = $this->subject->search('awesome');

        $this->assertTrue($result->getCount() === 1);
    }

    public function testSearchWithScores()
    {
        $result = $this->subject->withScores()->search('awesome');

        $this->assertTrue($result->getCount() === 1);
        $this->assertTrue(property_exists($result->getDocuments()[0], 'score'));
    }

    public function testSearchWithPayloads()
    {
        $result = $this->subject->withPayloads()->search('awesome');

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
}
