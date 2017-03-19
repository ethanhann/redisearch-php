<?php

namespace Eeh\Tests\RediSearch;

use Eeh\RediSearch\Exceptions\NoFieldsInIndexException;
use Eeh\RediSearch\Fields\GeoLocation;
use Eeh\RediSearch\Fields\NumericField;
use Eeh\RediSearch\Fields\TextField;
use Eeh\RediSearch\IndexInterface;
use Eeh\RediSearch\Redis\RedisClient;
use Eeh\Tests\Stubs\TestDocument;
use Eeh\Tests\Stubs\TestIndex;
use Eeh\Tests\Stubs\IndexWithoutFields;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class ClientTest extends TestCase
{
    private $indexName;
    /** @var IndexInterface */
    private $subject;
    /** @var RedisClient */
    private $redisClient;

    public function setUp()
    {
        $this->indexName = 'ClientTest';
        $this->redisClient = new RedisClient(
            \Redis::class,
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
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
        $this->assertEquals($result->getCount(), 1);
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

        $this->assertEquals($result->getCount(), 2);
    }

    public function testSearchWithPredis()
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host' => getenv('REDIS_HOST') ?? '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?? 6379,
            'database' => getenv('REDIS_DB') ?? 0,
        ]);
        $redis->connect();
        $redisClient = new RedisClient($redis);
        $subject = (new TestIndex($redisClient, 'PredisClientTest'))
            ->addTextField('title')
            ->addTextField('author')
            ->addNumericField('price')
            ->addNumericField('stock');
        $subject->create();
        $subject->add([
            new TextField('title', 'How to be awesome: Part 1.'),
            new TextField('author', 'Jack'),
        ]);
        $subject->add([
            new TextField('title', 'How to be awesome: Part 2.'),
            new TextField('author', 'Jack'),
        ]);

        $result = $subject->search('awesome');

        $this->assertEquals($result->getCount(), 2);
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
        $index = (new TestIndex())
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

        $this->assertEquals($result->getCount(), 1);
    }
}
