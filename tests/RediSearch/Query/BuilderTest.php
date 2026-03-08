<?php

namespace Ehann\Tests\RediSearch\Query;

use Ehann\RediSearch\Fields\GeoLocation;
use Ehann\RediSearch\Query\Builder;
use Ehann\Tests\Stubs\TestIndex;
use Ehann\Tests\RediSearchTestCase;

class BuilderTest extends RediSearchTestCase
{
    private Builder $subject;
    private array $expectedResult1;
    private array $expectedResult2;
    private array $expectedResult3;

    public function setUp(): void
    {
        parent::setUp();
        $this->indexName = 'QueryBuilderTest';
        $index = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title')
            ->addTextField('author')
            ->addNumericField('price')
            ->addNumericField('stock')
            ->addGeoField('location')
            ->addTextField('private', 1.0, false, true);
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
            'title' => 'Shoes in the 22nd Century',
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
            'private' => 'classified'
        ];
        $index->add($this->expectedResult3);
        $this->subject = (new Builder($this->redisClient, $this->indexName));
    }

    public function tearDown(): void
    {
        $this->redisClient->flushAll();
    }

    public function testSearch(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->search('Shoes');

        // Assert
        $this->assertTrue($result->getCount() === 1);
    }

    public function testGetCountDirectly(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->count('Shoes');

        // Assert
        $this->assertTrue($result === 1);
    }

    public function testReturnsZeroResultsWhenNotIndexed(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->search('classified');

        // Assert
        $this->assertTrue($result->getCount() === 0);
    }

    public function testSearchWithReturn(): void
    {
        // Arrange
        $expectedAuthor = 'Jessica';

        // Act
        $result = $this->subject->return(['author'])->search('Shoes');

        // Assert
        $firstResult = $result->getDocuments()[0];
        $this->assertSame($expectedAuthor, $firstResult->author);
        $this->assertTrue(property_exists($firstResult, 'author'));
        $this->assertFalse(property_exists($firstResult, 'title'));
    }

    public function testSearchWithSummarize(): void
    {
        // Arrange
        $expectedTitle = 'Shoes in the 22nd...';

        // Act
        $result = $this->subject->summarize(['title', 'author'])->search('Shoes');

        // Assert
        $firstResult = $result->getDocuments()[0];
        $this->assertSame($expectedTitle, $firstResult->title);
    }

    public function testSearchWithHighlight(): void
    {
        // Arrange
        $expectedTitle = '<strong>Shoes</strong> in the 22nd Century';

        // Act
        $result = $this->subject->highlight(['title', 'author'])->search('Shoes');

        // Assert
        $firstResult = $result->getDocuments()[0];
        $this->assertSame($expectedTitle, $firstResult->title);
    }

    public function testSearchWithScores(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->withScores()->search('Shoes');

        // Assert
        $this->assertTrue($result->getCount() === 1);
        $this->assertTrue(property_exists($result->getDocuments()[0], 'score'));
    }

    public function testSearchWithPayloads(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->withPayloads()->search('Shoes');

        // Assert
        $this->assertSame(1, $result->getCount());
        $this->assertTrue(property_exists($result->getDocuments()[0], 'payload'));
    }

    public function testVerbatimSearch(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->verbatim()->search('Shoes in the 22nd Century');

        // Assert
        $this->assertSame(1, $result->getCount());
    }

    public function testVerbatimSearchFails(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->verbatim()->search('Shoess');

        // Assert
        $this->assertSame(0, $result->getCount());
    }

    public function testNumericRangeQuery(): void
    {
        // Arrange
        $expectedCount = 1;

        // Act
        $result = $this->subject
            ->numericFilter('price', 8, 10)
            ->search();

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertSame($this->expectedResult1['author'], $result->getDocuments()[0]->author);
    }

    public function testGeoQuery(): void
    {
        // Arrange
        $expectedCount = 1;

        // Act
        $result = $this->subject
            ->geoFilter('location', '51.0544782', '3.7178716', '100', 'km')
            ->search('Shoes');

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertSame($this->expectedResult2['author'], $result->getDocuments()[0]->author);
    }

    public function testGeoQueryWithoutSearchTerm(): void
    {
        // Arrange
        $expectedCount = 1;

        // Act
        $result = $this->subject
            ->geoFilter('location', '51.0544782', '3.7178716', '100', 'km')
            ->search();

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertSame($this->expectedResult2['author'], $result->getDocuments()[0]->author);
    }

    public function testLimitSearch(): void
    {
        // Arrange
        $expectedCount = 1;

        // Act
        $result = $this->subject->limit(0, $expectedCount)->search('How');

        // Assert
        $this->assertCount($expectedCount, $result->getDocuments());
    }

    public function testSearchWithNoContent(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->noContent()->search('How');

        // Assert
        $this->assertFalse(property_exists($result->getDocuments()[0], 'title'));
        $this->assertFalse(property_exists($result->getDocuments()[1], 'title'));
    }

    public function testSearchWithDefaultSlop(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->slop(0)->search('How appendix');

        // Assert
        $this->assertCount(0, $result->getDocuments());
    }

    public function testSearchWithNonDefaultSlop(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->slop(10)->search('How awesome');

        // Assert
        $this->assertCount(2, $result->getDocuments());
    }

    public function testExplainSimpleSearchQuery(): void
    {
        // Arrange
        $expectedInExplanation = 'INTERSECT';

        // Act
        $result = $this->subject->explain('How awesome');

        // Assert
        $this->assertStringContainsString($expectedInExplanation, $result);
    }

    public function testExplainComplexSearchQuery(): void
    {
        // Arrange
        $expectedInExplanation1 = 'INTERSECT';
        $expectedInExplanation2 = 'UNION';

        // Act
        $result = $this->subject->explain('(How awesome)|(22st Century)');

        // Assert
        $this->assertStringContainsString($expectedInExplanation1, $result);
        $this->assertStringContainsString($expectedInExplanation2, $result);
    }

    public function testSearchWithScorerFunction(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->scorer('DISMAX')->search('Shoes');

        // Assert
        $this->assertTrue($result->getCount() === 1);
    }

    public function testSearchWithSortBy(): void
    {
        // Arrange
        $indexName = 'QueryBuilderSortByTest';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title')
            ->addTextField('author', true)
            ->addNumericField('price', true)
            ->addNumericField('stock')
            ->addGeoField('location');
        $index->create();
        $expectedResult1 = [
            'title' => 'Cheapest book ever.',
            'author' => 'Jane',
            'price' => 99.01,
            'stock' => 55,
            'location' => new GeoLocation(10.9190500, 52.0504100),
        ];
        $index->add($expectedResult1);
        $expectedResult2 = [
            'title' => 'Ok book.',
            'author' => 'John',
            'price' => 10.50,
            'stock' => 66,
            'location' => new GeoLocation(10.9190500, 52.0504100),
        ];
        $index->add($expectedResult2);
        $expectedResult3 = [
            'title' => 'Expensive book.',
            'author' => 'John',
            'price' => 1000,
            'stock' => 77,
            'location' => new GeoLocation(10.9190500, 52.0504100),
        ];
        $index->add($expectedResult3);

        // Act
        $result = (new Builder($this->redisClient, $indexName))
            ->sortBy('price')
            ->search('book');

        // Assert
        $this->assertSame($expectedResult1['title'], $result->getDocuments()[1]->title);
        $this->assertSame($expectedResult2['title'], $result->getDocuments()[0]->title);
        $this->assertSame($expectedResult3['title'], $result->getDocuments()[2]->title);
    }
}
