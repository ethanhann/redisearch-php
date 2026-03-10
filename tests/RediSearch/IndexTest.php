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
use Ehann\RediSearch\Fields\GeoField;
use Ehann\RediSearch\Fields\GeoLocation;
use Ehann\RediSearch\Fields\VectorField;
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
    private IndexInterface $subject;

    public function setUp(): void
    {
        parent::setUp();
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

    public function testShouldFailToCreateIndexWhenThereAreNoFieldsDefined(): void
    {
        // Arrange
        $index = new IndexWithoutFields($this->redisClient, $this->indexName);

        // Assert
        $this->expectException(NoFieldsInIndexException::class);

        // Act
        $index->create();
    }

    public function testShouldCreateIndex(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->create();

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldVerifyIndexExists(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->exists();

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldVerifyIndexDoesNotExist(): void
    {
        // Arrange — see setUp()

        // Act
        $result = $this->subject->exists();

        // Assert
        $this->assertFalse($result);
    }

    public function testShouldDropIndex(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->drop();

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldGetInfo(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->info();

        // Assert
        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result) > 0);
    }

    public function testShouldLoadFieldsFromExistingIndex(): void
    {
        // Arrange — create the full index (title, author, price, stock, place, color)
        $this->subject->create();

        // Act — create a fresh Index with no fields defined and load from Redis
        $freshIndex = (new TestIndex($this->redisClient, $this->indexName))->loadFields();

        // Assert — all six fields are loaded with correct types
        $fields = $freshIndex->getFields();
        $this->assertArrayHasKey('title', $fields);
        $this->assertInstanceOf(TextField::class, $fields['title']);
        $this->assertArrayHasKey('author', $fields);
        $this->assertInstanceOf(TextField::class, $fields['author']);
        $this->assertArrayHasKey('price', $fields);
        $this->assertInstanceOf(NumericField::class, $fields['price']);
        $this->assertArrayHasKey('stock', $fields);
        $this->assertInstanceOf(NumericField::class, $fields['stock']);
        $this->assertArrayHasKey('place', $fields);
        $this->assertInstanceOf(GeoField::class, $fields['place']);
        $this->assertArrayHasKey('color', $fields);
        $this->assertInstanceOf(TagField::class, $fields['color']);
    }

    public function testLoadedFieldsCanBeUsedToMakeDocuments(): void
    {
        // Arrange
        $this->subject->create();
        $freshIndex = (new TestIndex($this->redisClient, $this->indexName))->loadFields();

        // Act — makeDocument() requires fields to be defined
        $document = $freshIndex->makeDocument('doc1');
        $document->title->setValue('Test Book');
        $result = $freshIndex->add($document);

        // Assert
        $this->assertTrue($result);
    }

    public function testLoadFieldsShouldNotIncludeInternalFields(): void
    {
        // Arrange
        $this->subject->create();
        $freshIndex = new TestIndex($this->redisClient, $this->indexName);

        // Act
        $freshIndex->loadFields();

        // Assert — only the 6 user-defined fields, no __score / __language
        $this->assertCount(6, $freshIndex->getFields());
        $this->assertArrayNotHasKey('__score', $freshIndex->getFields());
        $this->assertArrayNotHasKey('__language', $freshIndex->getFields());
    }

    public function testLoadFieldsShouldRestoreTextFieldWeight(): void
    {
        // Arrange
        $indexName = 'LoadFieldsWeightTest';
        (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', 2.5)
            ->create();
        $freshIndex = new TestIndex($this->redisClient, $indexName);

        // Act
        $freshIndex->loadFields();

        // Assert
        $this->assertInstanceOf(TextField::class, $freshIndex->getFields()['title']);
        $this->assertEquals(2.5, $freshIndex->getFields()['title']->getWeight());
    }

    public function testLoadFieldsShouldRestoreSortableFlag(): void
    {
        // Arrange
        $indexName = 'LoadFieldsSortableTest';
        (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', 1.0, true)
            ->create();
        $freshIndex = new TestIndex($this->redisClient, $indexName);

        // Act
        $freshIndex->loadFields();

        // Assert
        $this->assertTrue($freshIndex->getFields()['title']->isSortable());
    }

    public function testLoadFieldsShouldRestoreTagFieldSeparator(): void
    {
        // Arrange
        $indexName = 'LoadFieldsSeparatorTest';
        (new TestIndex($this->redisClient, $indexName))
            ->addTagField('keywords', false, false, '|')
            ->create();
        $freshIndex = new TestIndex($this->redisClient, $indexName);

        // Act
        $freshIndex->loadFields();

        // Assert
        $this->assertInstanceOf(TagField::class, $freshIndex->getFields()['keywords']);
        $this->assertSame('|', $freshIndex->getFields()['keywords']->getSeparator());
    }

    public function testLoadFieldsShouldReturnSelfForFluentChaining(): void
    {
        // Arrange
        $this->subject->create();
        $freshIndex = new TestIndex($this->redisClient, $this->indexName);

        // Act
        $result = $freshIndex->loadFields();

        // Assert
        $this->assertSame($freshIndex, $result);
    }

    public function testShouldDeleteDocumentById(): void
    {
        // Arrange
        $this->subject->create();
        $expectedId = 'kasdoi13hflkhfdls';
        $document = $this->subject->makeDocument($expectedId);
        $document->title->setValue('My New Book');
        $document->author->setValue('Jack');
        $document->price->setValue(123);
        $document->stock->setValue(1123);
        $this->subject->add($document);

        // Act
        $result = $this->subject->delete($expectedId);

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($this->subject->search('My New Book')->getDocuments());
    }

    public function testShouldPhysicallyDeleteDocumentById(): void
    {
        // Arrange
        $this->subject->create();
        $expectedId = 'fio4oihfohsdfl';
        $document = $this->subject->makeDocument($expectedId);
        $document->title->setValue('My New Book');
        $document->author->setValue('Jack');
        $document->price->setValue(123);
        $document->stock->setValue(1123);
        $this->subject->add($document);

        // Act
        $result = $this->subject->delete($expectedId, true);

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($this->subject->search('My New Book')->getDocuments());
    }

    public function testCreateIndexWithSortableFields(): void
    {
        // Arrange
        $indexName = 'IndexWithSortableFieldsTest';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', true)
            ->addTextField('author', true)
            ->addNumericField('price', true)
            ->addNumericField('stock', true);

        // Act
        $result = $index->create();

        // Assert
        $this->assertTrue($result);
    }

    public function testCreateIndexWithNoindexFields(): void
    {
        // Arrange
        $indexName = 'IndexWithNoindexFields';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', true)
            ->addTextField('text_noindex', true, true)
            ->addNumericField('numeric_noindex', true)
            ->addGeoField('geo_noindex', true);

        // Act
        $result = $index->create();

        // Assert
        $this->assertTrue($result);
    }

    public function testCreateIndexWithTagField(): void
    {
        // Arrange
        $indexName = 'IndexWithTag';
        $index = (new TestIndex($this->redisClient, $indexName))
            ->addTextField('title', true)
            ->addTagField('tagfield', true, false, ',');

        // Act
        $result = $index->create();

        // Assert
        $this->assertTrue($result);
    }

    public function testGetTagValues(): void
    {
        // Arrange
        $expectedTagCount = 2;
        $blue = 'blue';
        $red = 'red';
        $this->subject->create();
        $this->subject->add([
            new TextField('title', 'How to be awesome.'),
            new TextField('author', 'Jack'),
            new NumericField('price', 9.99),
            new NumericField('stock', 231),
            new TagField('color', $red),
        ]);
        $this->subject->add([
            new TextField('title', 'F.O.W.L'),
            new TextField('author', 'Jill'),
            new NumericField('price', 19.99),
            new NumericField('stock', 31),
            new TagField('color', $blue),
        ]);

        // Act
        $actual = $this->subject->tagValues('color');

        // Assert
        $this->assertContains($blue, $actual);
        $this->assertContains($red, $actual);
        $this->assertSame($expectedTagCount, count($actual));
    }

    public function testAddDocumentWithZeroScore(): void
    {
        // Arrange
        $this->subject->create();
        $document = $this->subject->makeDocument();
        $expectedTitle = 'Tale of Two Cities';
        $document->title = FieldFactory::make('title', $expectedTitle);
        $expectedScore = 0.0;
        $document->setScore($expectedScore);
        $this->subject->add($document);

        // Act
        $result = $this->subject->withScores()->search($expectedTitle);

        // Assert
        $firstDocument = $result->getDocuments()[0];
        $this->assertEquals($expectedScore, $firstDocument->score);
        $this->assertSame($expectedTitle, $firstDocument->title);
    }

    public function testAddDocumentWithNonDefaultScore(): void
    {
        // Arrange
        $this->subject->create();
        $document = $this->subject->makeDocument();
        $expectedTitle = 'Tale of Two Cities';
        $document->title = FieldFactory::make('title', $expectedTitle);
        $document->setScore(0.9);
        $this->subject->add($document);

        // Act
        $result = $this->subject->withScores()->search($expectedTitle);

        // Assert
        $firstDocument = $result->getDocuments()[0];
        $this->assertNotEquals(2.0, $firstDocument->score);
        $this->assertSame($expectedTitle, $firstDocument->title);
    }

    public function testAddDocumentUsingArrayOfFieldsCreatedWithFieldFactory(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->add([
            FieldFactory::make('title', 'How to be awesome.'),
            FieldFactory::make('author', 'Jack'),
            FieldFactory::make('price', 9.99),
            FieldFactory::make('stock', 231),
            FieldFactory::make('place', new GeoLocation(-77.0366, 38.8977)),
            FieldFactory::make('color', new Tag('red')),
        ]);

        // Assert
        $this->assertTrue($result);
    }

    public function testAddDocumentUsingArrayOfFields(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->add([
            new TextField('title', 'How to be awesome.'),
            new TextField('author', 'Jack'),
            new NumericField('price', 9.99),
            new NumericField('stock', 231),
            new TagField('color', 'red'),
        ]);

        // Assert
        $this->assertTrue($result);
    }

    public function testAddDocumentUsingAssociativeArrayOfValues(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->add([
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ]);

        // Assert
        $this->assertTrue($result);
    }

    public function testAddDocument(): void
    {
        // Arrange
        $this->subject->create();
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);

        // Act
        $result = $this->subject->add($document);

        // Assert
        $this->assertTrue($result);
    }

    public function testAddDocumentWithUnsupportedLanguage(): void
    {
        // Arrange
        $this->subject->create();
        $document = $this->subject->makeDocument();
        $document->setLanguage('foo');
        $document->title->setValue('How to be awesome.');

        // Assert
        $this->expectException(UnsupportedRediSearchLanguageException::class);

        // Act
        $this->subject->add($document);
    }

    public function testSearchWithUnsupportedLanguage(): void
    {
        // Arrange
        $this->subject->create();

        // Assert
        $this->expectException(UnsupportedRediSearchLanguageException::class);

        // Act
        $this->subject->language('foo')->search('bar');
    }

    public function testAddDocumentToIndexWithAnUndefinedField(): void
    {
        // Arrange
        $this->subject->create();

        // Assert
        $this->expectException(FieldNotInSchemaException::class);

        // Act
        $this->subject->add(['foo' => 'bar']);
    }

    public function testAddDocumentToUndefinedIndex(): void
    {
        // Arrange
        $index = new Index($this->redisClient);
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');

        // Assert
        $this->expectException(UnknownIndexNameException::class);

        // Act
        $result = $index->add($document);

        $this->assertFalse($result);
    }

    public function testAddDocumentAlreadyInIndex(): void
    {
        // Arrange
        $this->subject->create();
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('How to be awesome.');
        $this->subject->add($document);

        // Assert
        $this->expectException(DocumentAlreadyInIndexException::class);

        // Act
        $result = $this->subject->add($document);

        $this->assertFalse($result);
    }

    public function testReplaceDocument(): void
    {
        // Arrange
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

        // Act
        $isUpdated = $this->subject->replace($document);

        // Assert
        $result = $this->subject->numericFilter('price', 12.99)->search('Part 2');
        $this->assertTrue($isUpdated);
        $this->assertSame(1, $result->getCount());
    }

    public function testAddDocumentFromHash(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->addHash([
            'title' => 'How to be awesome',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231
        ]);

        // Assert
        $this->assertTrue($result);
    }

    public function testFindDocumentAddedWithHash(): void
    {
        // Arrange
        $this->subject->create();
        $title = 'How to be awesome';
        $this->subject->addHash([
            'title' => 'How to be awesome',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231
        ]);

        // Act
        $result = $this->subject->search($title);

        // Assert
        $this->assertSame(1, $result->getCount());
        $this->assertSame($title, $result->getDocuments()[0]->title);
    }

    public function testReplaceDocumentFromHash(): void
    {
        // Arrange
        $this->subject->create();
        $id = 'gooblegobble';
        /** @var TestDocument $originalDocument */
        $originalDocument = $this->subject->makeDocument($id);
        $originalDocument->title->setValue('How to be awesome.');
        $originalDocument->author->setValue('Jack');
        $originalDocument->price->setValue(9.99);
        $originalDocument->stock->setValue(231);
        $this->subject->add($originalDocument);
        /** @var TestDocument $hashDocument */
        $hashDocument = $this->subject->makeDocument($id);
        $hashDocument->title->setValue('Farming For Fun');
        $hashDocument->author->setValue('Fred');
        $hashDocument->price->setValue(19.99);
        $hashDocument->stock->setValue(200);

        // Act
        $hasAdded = $this->subject->addHash($hashDocument);

        // Assert
        $this->assertTrue($hasAdded);
        $searchResult = $this->subject->search('Farming');
        $this->assertSame($id, $searchResult->getDocuments()[0]->id);
    }

    public function testSearch(): void
    {
        // Arrange
        $this->subject->create();
        $this->subject->add([
            new TextField('title', 'How to be awesome: Part 1.'),
            new TextField('author', 'Jack'),
        ]);
        $this->subject->add([
            new TextField('title', 'How to be awesome: Part 2.'),
            new TextField('author', 'Jack'),
        ]);

        // Act
        $result = $this->subject->search('awesome');

        // Assert
        $this->assertSame(2, $result->getCount());
    }

    public function testGetCountDirectly(): void
    {
        // Arrange
        $this->subject->create();
        $this->subject->add([
            new TextField('title', 'How to be awesome: Part 1.'),
            new TextField('author', 'Jack'),
        ]);
        $this->subject->add([
            new TextField('title', 'How to be awesome: Part 2.'),
            new TextField('author', 'Jack'),
        ]);

        // Act
        $result = $this->subject->count('awesome');

        // Assert
        $this->assertTrue($result === 2);
    }

    public function testSearchForNumeric(): void
    {
        // Arrange
        $this->subject->create();
        $this->subject->add([
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ]);

        // Act
        $result = $this->subject
            ->numericFilter('price', 1, 500)
            ->search('awesome');

        // Assert
        $this->assertSame(1, $result->getCount());
    }

    public function testAddDocumentWithGeoField(): void
    {
        // Arrange
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

        // Act
        $result = $index
            ->geoFilter('place', -77.0366, 38.897, 100)
            ->numericFilter('population', 1, 500)
            ->search('Foo');

        // Assert
        $this->assertSame(1, $result->getCount());
    }

    public function testAddDocumentWithTagField(): void
    {
        // Arrange
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addNumericField('population')
            ->addTagField('color')
            ->create();
        $index->add(['name' => 'Foo', 'color' => 'red']);
        $index->add(['name' => 'Bar', 'color' => 'blue']);
        $index->add(['name' => 'Baz', 'color' => 'sky blue']);
        $index->add(['name' => 'Qux', 'color' => 'sugar-cookie']);

        // Act
        $result = $index
            ->tagFilter('color', ['sugar-cookie'])
            ->search();

        // Assert
        $this->assertSame(1, $result->getCount());
    }

    public function testAddDocumentWithTagFieldAndAlternateTagSeparator(): void
    {
        // Arrange
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addNumericField('population')
            ->addTagField('color', '^^^')
            ->create();
        $index->add(['name' => 'Foo', 'color' => 'red']);
        $index->add(['name' => 'Bar', 'color' => 'blue']);

        // Act
        $result = $index
            ->tagFilter('color', ['blue'])
            ->search();

        // Assert
        $this->assertSame(1, $result->getCount());
    }

    public function testFilterTagFieldsAsUnionOfDocuments(): void
    {
        // Arrange
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addTagField('color')
            ->create();
        $index->add(['name' => 'Foo', 'color' => 'red']);
        $index->add(['name' => 'Bar', 'color' => 'blue']);

        // Act
        $result = $index
            ->tagFilter('color', ['blue', 'red'])
            ->search();

        // Assert
        $this->assertSame(2, $result->getCount());
    }

    public function testFilterTagFieldsAsIntersectionOfDocuments(): void
    {
        // Arrange
        $index = (new TestIndex($this->redisClient))
            ->setIndexName('TagTest');
        $index
            ->addTextField('name')
            ->addTagField('color')
            ->create();
        $index->add(['name' => 'Foo', 'color' => 'red']);
        $index->add(['name' => 'Bar', 'color' => 'blue']);
        $index->add(['name' => 'Bar', 'color' => 'red,yellow']);

        // Act
        $result = $index
            ->tagFilter('color', ['red'])
            ->tagFilter('color', ['yellow'])
            ->search();

        // Assert
        $this->assertSame(1, $result->getCount());
    }

    public function testAddDocumentWithCustomId(): void
    {
        // Arrange
        $this->subject->create();
        $expectedId = '1';
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument($expectedId);
        $document->title->setValue('How to be awesome.');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);

        // Act
        $isDocumentAdded = $this->subject->add($document);
        $result = $this->subject->search('How to be awesome.');

        // Assert
        $this->assertTrue($isDocumentAdded);
        $this->assertSame(1, $result->getCount());
        $this->assertSame($expectedId, $result->getDocuments()[0]->id);
    }

    public function testBatchIndexWithAdd(): void
    {
        // Arrange
        $this->subject->create();
        $expectedDocumentCount = 10;
        $documents = $this->makeDocuments();
        $expectedCount = count($documents);

        // Act
        $start = microtime(true);
        foreach ($documents as $document) {
            $this->subject->add($document);
        }
        print 'Batch insert time: ' . round(microtime(true) - $start, 4) . PHP_EOL;
        $result = $this->subject->search('How to be awesome.');

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertSame($expectedDocumentCount, count($result->getDocuments()));
    }

    public function testBatchIndexWithAddMany(): void
    {
        // Arrange
        $this->subject->create();
        $expectedDocumentCount = 10;
        $documents = $this->makeDocuments();
        $expectedCount = count($documents);

        // Act
        $start = microtime(true);
        $this->subject->addMany($documents);
        print 'Batch insert time: ' . round(microtime(true) - $start, 4) . PHP_EOL;
        $result = $this->subject->search('How to be awesome.');

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertSame($expectedDocumentCount, count($result->getDocuments()));
    }

    #[PHPUnit\Framework\Attributes\RequiresPhpExtension('redis')]
    public function testBatchIndexWithAddManyUsingPhpRedisWithAtomicityDisabled(): void
    {
        // Arrange
        if (!$this->isUsingPhpRedis()) {
            $this->markTestSkipped('Skipping because test suite is not configured to use PhpRedis.');
        }

        $rediSearchRedisClient = new RediSearchRedisClient($this->makePhpRedisAdapter());
        $rediSearchRedisClient->setLogger($this->logger);
        $this->subject
            ->setRedisClient($rediSearchRedisClient)
            ->create();
        $expectedDocumentCount = 10;
        $documents = $this->makeDocuments();
        $expectedCount = count($documents);

        // Act
        $start = microtime(true);
        $this->subject->addMany($documents, true);
        print 'Batch insert time: ' . round(microtime(true) - $start, 4) . PHP_EOL;
        $result = $this->subject->search('How to be awesome.');

        // Assert
        $this->assertSame($expectedCount, $result->getCount());
        $this->assertSame($expectedDocumentCount, count($result->getDocuments()));
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

    public function testShouldCreateIndexWithImplicitName(): void
    {
        // Arrange
        $bookIndex = new Index($this->redisClient);

        // Act
        $result1 = $bookIndex->addTextField('title')->create();
        $result2 = $bookIndex->add([
            new TextField('title', 'Tale of Two Cities'),
        ]);

        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function testSetStopWordsOnCreateIndex(): void
    {
        // Arrange
        $this->subject->setStopWords(['Awesome'])->create();
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue('Awesome');
        $document->author->setValue('Jack');
        $document->price->setValue(9.99);
        $document->stock->setValue(231);
        $isDocumentAdded = $this->subject->add($document);

        // Act
        $resultForStopWord = $this->subject->search('Awesome');
        $resultForNonStopWord = $this->subject->search('Jack');

        // Assert
        $this->assertTrue($isDocumentAdded);
        $this->assertSame(0, $resultForStopWord->getCount());
        $this->assertSame(1, $resultForNonStopWord->getCount());
    }

    public function testShouldNotSearchEveryIndexWhenAPrefixIsSpecified(): void
    {
        // Arrange
        $expectedFirstResult = 'Jack';
        $firstPrefix = 'Foo';
        $secondPrefix = 'Bar';
        $firstIndex = (new Index($this->redisClient, 'first'))
            ->setPrefixes([$firstPrefix])
            ->addTextField('name');
        $firstIndex->create();
        $firstIndex->addHash(['name' => $expectedFirstResult]);

        $secondIndex = (new Index($this->redisClient, 'second'))
            ->setPrefixes([$secondPrefix])
            ->addTextField('name');
        $secondIndex->create();

        // Act
        $firstResult = $firstIndex->search($expectedFirstResult);
        $secondResult = $secondIndex->search($expectedFirstResult);

        // Assert
        $this->assertSame(1, $firstResult->getCount());
        $this->assertSame(0, $secondResult->getCount());
        $this->assertSame($expectedFirstResult, $firstResult->getDocuments()[0]->name);
    }

    public function testShouldSearchEveryIndexWhenAPrefixIsNotSpecified(): void
    {
        // Arrange
        $expectedDocuments = 1;
        $expectedName = 'Jack';
        $firstIndex = (new Index($this->redisClient, 'first'))->addTextField('name');
        $firstIndex->create();
        $firstIndex->add([new TextField('name', $expectedName)]);
        $secondIndex = (new Index($this->redisClient, 'second'))->addTextField('name');
        $secondIndex->create();

        // Act
        $firstResult = $firstIndex->search($expectedName);
        $secondResult = $secondIndex->search($expectedName);

        // Assert
        $this->assertSame($expectedDocuments, $firstResult->getCount());
        $this->assertSame($expectedDocuments, $secondResult->getCount());
        $this->assertSame($expectedName, $firstResult->getDocuments()[0]->name);
        $this->assertSame($expectedName, $secondResult->getDocuments()[0]->name);
    }

    public function testShouldCreateIndexWithNoFrequencies(): void
    {
        // Arrange
        $this->subject->setNoFrequenciesEnabled(true)->create();
        $expected = 'NOFREQS';

        // Act
        $info = $this->subject->info();

        // Assert
        $this->assertEquals($expected, $info[3][0]);
    }

    public function testShouldNotChangeOriginalSchemaFieldWhenAddingNewDocument(): void
    {
        // Arrange
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

        // Act — verify first document is unaffected by creation of second document
        $actualId = $documents[0]->getId();
        $actualTitle = $documents[0]->title->getValue();

        // Assert
        $this->assertSame($expectedId, $actualId);
        $this->assertSame($expectedTitle, $actualTitle);
    }

    public function testShouldCreateAlias(): void
    {
        // Arrange
        $this->subject->create();

        // Act
        $result = $this->subject->addAlias('MyAlias');

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldUpdateAlias(): void
    {
        // Arrange
        $this->subject->create();
        $this->subject->addAlias('MyAlias');
        $index = (new Index($this->redisClient, 'Second'))
            ->addTextField('foo');
        $index->create();

        // Act
        $result = $index->updateAlias('MyAlias');

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldDeleteAlias(): void
    {
        // Arrange
        $this->subject->create();
        $this->subject->addAlias('MyAlias');

        // Act
        $result = $this->subject->deleteAlias('MyAlias');

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldFailToCreateAliasIfIndexDoesNotExist(): void
    {
        // Arrange — see setUp(), index not yet created

        // Assert
        $this->expectException(UnknownIndexNameOrNameIsAnAliasItselfException::class);

        // Act
        $this->subject->addAlias('MyAlias');
    }

    public function testShouldFailToUpdateAliasIfIndexDoesNotExist(): void
    {
        // Arrange — see setUp(), index not yet created

        // Assert
        $this->expectException(UnknownIndexNameOrNameIsAnAliasItselfException::class);

        // Act
        $this->subject->updateAlias('MyAlias');
    }

    public function testShouldFailToDeleteAliasIfIndexDoesNotExist(): void
    {
        // Arrange — see setUp(), index not yet created

        // Assert
        $this->expectException(AliasDoesNotExistException::class);

        // Act
        $this->subject->deleteAlias('MyAlias');
    }

    public function testShouldGetFields(): void
    {
        // Arrange
        $this->subject->create();
        $expectedTitle = 'title TEXT WEIGHT 1';
        $expectedAuthor = 'author TEXT WEIGHT 1';
        $expectedPrice = 'price NUMERIC';
        $expectedStock = 'stock NUMERIC';
        $expectedPlace = 'place GEO';
        $expectedColor = 'color TAG SEPARATOR ,';

        // Act
        $fields = $this->subject->getFields();

        // Assert
        $this->assertSame($expectedTitle, implode(' ', $fields['title']->getTypeDefinition()));
        $this->assertSame($expectedAuthor, implode(' ', $fields['author']->getTypeDefinition()));
        $this->assertSame($expectedPrice, implode(' ', $fields['price']->getTypeDefinition()));
        $this->assertSame($expectedStock, implode(' ', $fields['stock']->getTypeDefinition()));
        $this->assertSame($expectedPlace, implode(' ', $fields['place']->getTypeDefinition()));
        $this->assertSame($expectedColor, implode(' ', $fields['color']->getTypeDefinition()));
    }

    public function testShouldConvertAnArrayToDocument(): void
    {
        // Arrange
        $title = 'Your Honor';
        $arr = ['title' => $title];
        /** @var TestDocument $document */
        $document = $this->subject->makeDocument();
        $document->title->setValue($title);

        // Act
        /** @var TestDocument $documentFromArray */
        $documentFromArray = $this->subject->arrayToDocument($arr);

        // Assert
        $this->assertSame($title, $documentFromArray->title->getValue());
        $this->assertSame($title, $document->title->getValue());
        $this->assertSame(json_encode($document->title), json_encode($documentFromArray->title));
    }
}
