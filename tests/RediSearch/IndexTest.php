<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Exceptions\AliasDoesNotExistException;
use Ehann\RediSearch\Exceptions\DocumentAlreadyInIndexException;
use Ehann\RediSearch\Exceptions\FieldNotInSchemaException;
use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use Ehann\RediSearch\Exceptions\RediSearchException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameOrNameIsAnAliasItselfException;
use Ehann\RediSearch\Fields\Tag;
use Ehann\RediSearch\Fields\TagField;
use Ehann\RediSearch\Index;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Exceptions\UnsupportedRediSearchLanguageException;
use Ehann\RediSearch\Fields\FieldFactory;
use Ehann\RediSearch\Fields\GeoLocation;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\IndexInterface;
use Ehann\RediSearch\RediSearchRedisClient;
use Ehann\Tests\Stubs\TestDocument;
use Ehann\Tests\Stubs\TestIndex;
use Ehann\Tests\Stubs\IndexWithoutFields;
use Ehann\Tests\RediSearchTestCase;

class IndexTest extends RediSearchTestCase
{
    /** @var IndexInterface */
    private $subject;

    public function setUp(): void
    {
        $this->indexName = 'ClientTest';
        $this->subject = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title')
            ->addTextField('author')
            ->addNumericField('price')
            ->addNumericField('stock')
            ->addGeoField('place')
            ->addTagField('color');

        $this->logger->debug('setUp...');
    }

    public function tearDown(): void
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

    public function testShouldVerifyIndexExists()
    {
        $this->subject->create();

        $result = $this->subject->exists();

        $this->assertTrue($result);
    }

    public function testShouldVerifyIndexDoesNotExist()
    {
        $result = $this->subject->exists();

        $this->assertFalse($result);
    }

    public function testShouldDropIndex()
    {
        $this->subject->create();

        $result = $this->subject->drop();

        $this->assertTrue($result);
    }

    public function testShouldGetInfo()
    {
        $this->subject->create();

        $result = $this->subject->info();

        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result) > 0);
    }

    public function testShouldDeleteDocumentById()
    {
        $this->subject->create();
        $expectedId = 'kasdoi13hflkhfdls';
        $document = $this->subject->makeDocument($expectedId);
        $document->title->setValue('My New Book');
        $document->author->setValue('Jack');
        $document->price->setValue(123);
        $document->stock->setValue(1123);
        $this->subject->add($document);

        $result = $this->subject->delete($expectedId);

        $this->assertTrue($result);
        $this->assertEmpty($this->subject->search('My New Book')->getDocuments());
    }

    public function testShouldPhysicallyDeleteDocumentById()
    {
        $this->subject->create();
        $expectedId = 'fio4oihfohsdfl';
        $document = $this->subject->makeDocument($expectedId);
        $document->title->setValue('My New Book');
        $document->author->setValue('Jack');
        $document->price->setValue(123);
        $document->stock->setValue(1123);
        $this->subject->add($document);

        $result = $this->subject->delete($expectedId, true);

        $this->assertTrue($result);
        $this->assertEmpty($this->subject->search('My New Book')->getDocuments());
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

    public function testCreateIndexWithNoindexFields()
    {
        $indexName = 'IndexWithNoindexFields';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', true)
            ->addTextField('text_noindex', true, true)
            ->addNumericField('numeric_noindex', true)
            ->addGeoField('geo_noindex', true);

        $result = $index->create();
        $this->assertTrue($result);
    }

    public function testCreateIndexWithTagField()
    {
        $indexName = 'IndexWithTag';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', true)
            ->addTagField('tagfield', true, false, ',');


        $result = $index->create();
        $this->assertTrue($result);
    }

    public function testGetTagValues()
    {
        $this->subject->create();
        $this->subject->add([
            new TextField('title', 'How to be awesome.'),
            new TextField('author', 'Jack'),
            new NumericField('price', 9.99),
            new NumericField('stock', 231),
            new TagField('color', 'red'),
        ]);
        $this->subject->add([
            new TextField('title', 'F.O.W.L'),
            new TextField('author', 'Jill'),
            new NumericField('price', 19.99),
            new NumericField('stock', 31),
            new TagField('color', 'blue'),
        ]);
        $expected = ['red', 'blue'];

        $actual = $this->subject->tagValues('color');

        $this->assertEquals($expected, $actual);
    }

    public function testAddDocumentWithZeroScore()
    {
        $this->subject->create();
        $document = $this->subject->makeDocument();
        $expectedTitle = 'Tale of Two Cities';
        $document->title = FieldFactory::make('title', $expectedTitle);
        $expectedScore = 0.0;
        $document->setScore($expectedScore);
        $this->subject->add($document);

        $result = $this->subject->withScores()->search($expectedTitle);

        $firstDocument = $result->getDocuments()[0];
        $this->assertEquals($expectedScore, $firstDocument->score);
        $this->assertEquals($expectedTitle, $firstDocument->title);
    }

    public function testAddDocumentWithNonDefaultScore()
    {
        $this->subject->create();
        $document = $this->subject->makeDocument();
        $expectedTitle = 'Tale of Two Cities';
        $document->title = FieldFactory::make('title', $expectedTitle);
        $document->setScore(0.9);
        $this->subject->add($document);

        $result = $this->subject->withScores()->search($expectedTitle);

        $firstDocument = $result->getDocuments()[0];
        $this->assertNotEquals(2.0, $firstDocument->score);
        $this->assertEquals($expectedTitle, $firstDocument->title);
    }

    public function testAddDocumentUsingArrayOfFieldsCreatedWithFieldFactory()
    {
        $this->subject->create();

        $result = $this->subject->add([
            FieldFactory::make('title', 'How to be awesome.'),
            FieldFactory::make('author', 'Jack'),
            FieldFactory::make('price', 9.99),
            FieldFactory::make('stock', 231),
            FieldFactory::make('place', new GeoLocation(-77.0366, 38.8977)),
            FieldFactory::make('color', new Tag('red')),
        ]);

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
            new TagField('color', 'red'),
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

    public function testAddDocumentWithUnsupportedLanguage()
    {
        $this->subject->create();
        $document = $this->subject->makeDocument();
        $document->setLanguage('foo');
        $document->title->setValue('How to be awesome.');
        $this->expectException(UnsupportedRediSearchLanguageException::class);

        $this->subject->add($document);
    }

    public function testSearchWithUnsupportedLanguage()
    {
        $this->subject->create();
        $this->expectException(UnsupportedRediSearchLanguageException::class);

        $this->subject->language('foo')->search('bar');
    }

    public function testAddDocumentToIndexWithAnUndefinedField()
    {
        $this->subject->create();
        $this->expectException(FieldNotInSchemaException::class);

        $this->subject->add(['foo' => 'bar']);
    }

    public function testAddDocumentToUndefinedIndex()
    {
        $this->expectException(UnknownIndexNameException::class);
        $index = new Index($this->redisClient);
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');

        $result = $index->add($document);

        $this->assertFalse($result);
    }

    public function testAddDocumentAlreadyInIndex()
    {
        $this->subject->create();
        $this->expectException(DocumentAlreadyInIndexException::class);
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');
        $this->subject->add($document);

        $result = $this->subject->add($document);

        $this->assertFalse($result);
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

        $result = $this->subject->numericFilter('price', 12.99)->search('Part 2');
        $this->assertTrue($isUpdated);
        $this->assertEquals(1, $result->getCount());
    }

    public function testAddDocumentFromHash()
    {
        $this->subject->create();

        $result = $this->subject->addHash([
            'gooblegobble',
            'title',
            'How to be awesome',
            'author',
            'Jack',
            'price',
            9.99,
            'stock',
            231
        ]);

        $this->assertTrue($result);
    }

    public function testFindDocumentAddedWithHash ()
    {
        $this->subject->create();
        $title = 'How to be awesome';
        $this->redisClient->rawCommand('HSET', [
            'gooblegobble',
            'title',
            $title,
            'author',
            'Jack',
            'price',
            9.99,
            'stock',
            231
        ]);

        $result = $this->subject->search($title);

        $this->assertEquals(1, $result->getCount());
        $this->assertEquals($title, $result->getDocuments()[0]->title);
    }

    public function testReplaceDocumentFromHash()
    {
        $this->subject->create();
        $id = 'gooblegobble';
        /** @var TestDocument $expectedDocument */
        $expectedDocument = $this->subject->makeDocument($id);
        $expectedDocument->title->setValue('How to be awesome.');
        $expectedDocument->author->setValue('Jack');
        $expectedDocument->price->setValue(9.99);
        $expectedDocument->stock->setValue(231);
        $this->subject->add($expectedDocument);
        $this->redisClient->rawCommand('HSET', [
            $id,
            'title',
            'How to be awesome, Part 2',
            'author',
            'Jack',
            'price',
            9.99,
            'stock',
            231
        ]);
        $document = $this->subject->makeDocument($id);

        $result = $this->subject->addHash($document);

        $this->assertTrue($result);
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

    public function testGetCountDirectly()
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

        $result = $this->subject->count('awesome');

        $this->assertTrue($result === 2);
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
            ->search('Foo');

        $this->assertEquals(1, $result->getCount());
    }

    public function testAddDocumentWithTagField()
    {
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addNumericField('population')
            ->addTagField('color')
            ->create();
        $index->add([
            'name' => 'Foo',
            'color' => 'red',
        ]);
        $index->add([
            'name' => 'Bar',
            'color' => 'blue',
        ]);

        $result = $index
            ->tagFilter('color', ['blue'])
            ->search();

        $this->assertEquals(1, $result->getCount());
    }

    public function testAddDocumentWithTagFieldAndAlternateTagSeparator()
    {
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addNumericField('population')
            ->addTagField('color', '^^^')
            ->create();
        $index->add([
            'name' => 'Foo',
            'color' => 'red',
        ]);
        $index->add([
            'name' => 'Bar',
            'color' => 'blue',
        ]);

        $result = $index
            ->tagFilter('color', ['blue'])
            ->search();

        $this->assertEquals(1, $result->getCount());
    }

    public function testFilterTagFieldsAsUnionOfDocuments()
    {
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addTagField('color')
            ->create();
        $index->add([
            'name' => 'Foo',
            'color' => 'red',
        ]);
        $index->add([
            'name' => 'Bar',
            'color' => 'blue',
        ]);

        $result = $index
            ->tagFilter('color', ['blue', 'red'])
            ->search();

        $this->assertEquals(2, $result->getCount());
    }

    public function testFilterTagFieldsAsIntersectionOfDocuments()
    {
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addTagField('color')
            ->create();
        $index->add([
            'name' => 'Foo',
            'color' => 'red',
        ]);
        $index->add([
            'name' => 'Bar',
            'color' => 'blue',
        ]);

        $index->add([
            'name' => 'Bar',
            'color' => 'red,yellow',
        ]);

        $result = $index
            ->tagFilter('color', ['red'])
            ->tagFilter('color', ['yellow'])
            ->search();

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
        if (!$this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is not configured to use PhpRedis.');
        }

        $rediSearchRedisClient = new RediSearchRedisClient($this->makePhpRedisAdapter());
        $this->subject->setRedisClient($rediSearchRedisClient)->create();
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

    public function testShouldCreateIndexWithImplicitName()
    {
        $bookIndex = new Index($this->redisClient);

        $result1 = $bookIndex->addTextField('title')->create();
        $result2 = $bookIndex->add([
            new TextField('title', 'Tale of Two Cities'),
        ]);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function testSetStopWordsOnCreateIndex()
    {
        $this->subject->setStopWords(['Awesome'])->create();

        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('Awesome');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);

        $isDocumentAdded = $this->subject->add($document);
        $result = $this->subject->search('Awesome');
        $this->assertTrue($isDocumentAdded);
        $this->assertEquals(0, $result->getCount());

        $result = $this->subject->search('Jack');
        $this->assertEquals(1, $result->getCount());
    }

    public function testShouldCreateIndexWithNoFrequencies()
    {
        $this->subject->setNoFrequenciesEnabled(true)->create();
        $expected = 'NOFREQS';

        $info = $this->subject->info();

        $this->assertEquals($expected, $info[3][0]);
    }

    public function testShouldNotChangeOriginalSchemaFieldWhenAddingNewDocument()
    {
        $expectedId = 'id1';
        $expectedTitle = 'Foo';
        $documents = [];
        $newDocument = $this->subject->makeDocument();
        $newDocument->setId($expectedId);
        $newDocument->title->setValue($expectedTitle);
        $documents[] = $newDocument;

        $barDocument = $this->subject->makeDocument();
        $barDocument->setId('id2');
        $barDocument->title->setValue('Bar');

        $this->assertEquals($expectedId, $documents[0]->getId());
        $this->assertEquals($expectedTitle, $documents[0]->title->getValue());
    }

    public function testShouldCreateAlias()
    {
        $this->subject->create();

        $result = $this->subject->addAlias('MyAlias');

        $this->assertTrue($result);
    }

    public function testShouldUpdateAlias()
    {
        $this->subject->create();
        $this->subject->addAlias('MyAlias');
        $index = (new Index($this->redisClient, 'Second'))
            ->addTextField('foo');
        $index->create();

        $result = $index->updateAlias('MyAlias');

        $this->assertTrue($result);
    }

    public function testShouldDeleteAlias()
    {
        $this->subject->create();
        $this->subject->addAlias('MyAlias');

        $result = $this->subject->deleteAlias('MyAlias');

        $this->assertTrue($result);
    }

    public function testShouldFailToCreateAliasIfIndexDoesNotExist()
    {
        $this->expectException(UnknownIndexNameOrNameIsAnAliasItselfException::class);

        $this->subject->addAlias('MyAlias');
    }

    public function testShouldFailToUpdateAliasIfIndexDoesNotExist()
    {
        $this->expectException(UnknownIndexNameOrNameIsAnAliasItselfException::class);

        $this->subject->updateAlias('MyAlias');
    }

    public function testShouldFailToDeleteAliasIfIndexDoesNotExist()
    {
        $this->expectException(AliasDoesNotExistException::class);

        $this->subject->deleteAlias('MyAlias');
    }

    public function testShouldGetFields()
    {
        $this->subject->create();
        $expectedTitle = 'title TEXT WEIGHT 1';
        $expectedAuthor = 'author TEXT WEIGHT 1';
        $expectedPrice = 'price NUMERIC';
        $expectedStock = 'stock NUMERIC';
        $expectedPlace = 'place GEO';
        $expectedColor = 'color TAG SEPARATOR ,';

        $fields = $this->subject->getFields();

        $this->assertEquals($expectedTitle, implode(' ', $fields['title']->getTypeDefinition()));
        $this->assertEquals($expectedAuthor, implode(' ', $fields['author']->getTypeDefinition()));
        $this->assertEquals($expectedPrice, implode(' ', $fields['price']->getTypeDefinition()));
        $this->assertEquals($expectedStock, implode(' ', $fields['stock']->getTypeDefinition()));
        $this->assertEquals($expectedPlace, implode(' ', $fields['place']->getTypeDefinition()));
        $this->assertEquals($expectedColor, implode(' ', $fields['color']->getTypeDefinition()));
    }
}
