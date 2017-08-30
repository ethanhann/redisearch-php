<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use Ehann\RediSearch\Fields\GeoLocation;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\IndexInterface;
use Ehann\Tests\Stubs\TestDocument;
use Ehann\Tests\Stubs\TestIndex;
use Ehann\Tests\Stubs\IndexWithoutFields;
use Ehann\Tests\AbstractTestCase;

class IndexTest extends AbstractTestCase
{
    /** @var IndexInterface */
    private $subject;

    public function setUp()
    {
        $this->indexName = 'ClientTest';
        $this->subject = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title')
            ->addTextField('author')
            ->addNumericField('price')
            ->addNumericField('stock');
    }

    public function tearDown()
    {
        $this->redisClient->flushAll();
    }

    public function testShouldFailToCreateIndexWhenThereAreNoFieldsDefined()
    {
        $this->expectException(NoFieldsInIndexException::class);

        (new IndexWithoutFields($this->redisClient, $this->indexName))->create();
    }

    public function testShouldCreateIndex()
    {
        $result = $this->subject->create();

        $this->assertTrue($result || $result = 'OK');
    }

    public function testCreateIndexWithSortableFields()
    {
        $indexName = 'IndexWithSortableFieldsTest';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', true)
            ->addTextField('author', true)
            ->addNumericField('price', true)
            ->addNumericField('stock', true);
        $result = $index->create();
        $this->assertTrue($result);
    }

    public function testAddDocumentUsingArrayOfFields()
    {
        $this->subject->create();

        $result = $this->subject->add([
            new TextField('title', 'How to be awesome.'),
            new TextField('author', 'Jack'),
            new NumericField('price', 9.99),
            new NumericField('stock', 231),
        ]);

        $this->assertTrue($result);
    }

    public function testAddDocumentUsingAssociativeArrayOfValues()
    {
        $this->subject->create();

        $result = $this->subject->add([
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ]);

        $this->assertTrue($result);
    }

    public function testAddDocument()
    {
        $this->subject->create();
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);

        $result = $this->subject->add($document);

        $this->assertTrue($result);
    }

    public function testReplaceDocument()
    {
        $this->subject->create();
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);
        $this->subject->add($document);
        $document->title->setValue('How to be awesome: Part 2.');
        $document->price->setValue(19.99);

        $isUpdated = $this->subject->replace($document);

        $result = $this->subject->numericFilter('price', 19.99)->search('Part 2');
        $this->assertTrue($isUpdated);
        $this->assertEquals(1, $result->getCount());
    }

    public function testSearch()
    {
        $this->subject->create();
        $this->subject->add([
            new TextField('title', 'How to be awesome: Part 1.'),
            new TextField('author', 'Jack'),
        ]);
        $this->subject->add([
            new TextField('title', 'How to be awesome: Part 2.'),
            new TextField('author', 'Jack'),
        ]);

        $result = $this->subject->search('awesome');

        $this->assertEquals(2, $result->getCount());
    }

    public function testSearchForNumeric()
    {
        $this->subject->create();
        $this->subject->add([
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ]);

        $result = $this->subject
            ->numericFilter('price', 1, 500)
            ->search('awesome');

        $this->assertEquals($result->getCount(), 1);
    }

    public function testAddDocumentWithGeoField()
    {
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('GeoTest');
        $index
            ->addTextField('name')
            ->addNumericField('population')
            ->addGeoField('place')
            ->create();
        $index->add([
            'name' => 'Foo Bar',
            'population' => 231,
            'place' => new GeoLocation(-77.0366, 38.8977),
        ]);

        $result = $index
            ->geoFilter('place', -77.0366, 38.897, 100)
            ->numericFilter('population', 1, 500)
            ->search('Foo')
        ;

        $this->assertEquals(1, $result->getCount());
    }

    public function testAddDocumentWithCustomId()
    {
        $this->subject->create();
        $expectedId = '1';
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument($expectedId);
        $document->title->setValue('How to be awesome.');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);

        $isDocumentAdded = $this->subject->add($document);
        $result = $this->subject->search('How to be awesome.');

        $this->assertTrue($isDocumentAdded);
        $this->assertEquals(1, $result->getCount());
        $this->assertEquals($expectedId, $result->getDocuments()[0]->id);
    }

    public function testBatchIndexWithAdd()
    {
        $this->subject->create();
        $expectedDocumentCount = 10;
        $documents = $this->makeDocuments();
        $expectedCount = count($documents);

        $start = microtime(true);
        foreach ($documents as $document) {
            $this->subject->add($document);
        }
        print 'Batch insert time: ' . round(microtime(true) - $start, 4) . PHP_EOL;
        $result = $this->subject->search('How to be awesome.');

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedDocumentCount, count($result->getDocuments()));
    }


    public function testBatchIndexWithAddMany()
    {
        $this->subject->create();
        $expectedDocumentCount = 10;
        $documents = $this->makeDocuments();
        $expectedCount = count($documents);

        $start = microtime(true);
        $this->subject->addMany($documents);
        print 'Batch insert time: ' . round(microtime(true) - $start, 4) . PHP_EOL;
        $result = $this->subject->search('How to be awesome.');

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedDocumentCount, count($result->getDocuments()));
    }

    /**
     * @requires extension redis
     */
    public function testBatchIndexWithAddManyUsingPhpRedisWithAtomicityDisabled()
    {
        $this->subject->setRedisClient($this->makeRedisClientWithPhpRedis())->create();
        $expectedDocumentCount = 10;
        $documents = $this->makeDocuments();
        $expectedCount = count($documents);

        $start = microtime(true);
        $this->subject->addMany($documents, true);
        print 'Batch insert time: ' . round(microtime(true) - $start, 4) . PHP_EOL;
        $result = $this->subject->search('How to be awesome.');

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedDocumentCount, count($result->getDocuments()));
    }

    private function makeDocuments($count = 3000): array
    {
        $documents = [];
        foreach (range(1, $count) as $id) {
            $document = $this->subject->makeDocument($id);
            $document->title->setValue('How to be awesome.');
            $documents[] = $document;
        }
        return $documents;
    }
}
