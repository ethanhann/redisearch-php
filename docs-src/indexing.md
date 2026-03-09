# Indexing

## Field Types

There are five types of fields that can be added to a document: **TextField**, **NumericField**, **GeoField**, **TagField**, and **VectorField**.

They are instantiated like this:

```php-inline
new TextField('author', 'Charles Dickens');
new NumericField('price', 9.99);
new GeoField('place', new GeoLocation(-77.0366, 38.8977));
new TagField('color', 'red');
new VectorField('embedding', VectorField::ALGORITHM_HNSW, VectorField::TYPE_FLOAT32, 128, VectorField::DISTANCE_COSINE);
```

Fields can also be made with the FieldFactory class:

```php-inline
// Alternative syntax for: new TextField('author', 'Charles Dickens');
FieldFactory::make('author', 'Charles Dickens');

// Alternative syntax for: new NumericField('price', 9.99);
FieldFactory::make('price', 9.99);

// Alternative syntax for: new GeoField('place', new GeoLocation(-77.0366, 38.8977));
FieldFactory::make('place', new GeoLocation(-77.0366, 38.8977));

// Alternative syntax for: new TagField('color', 'red');
FieldFactory::make('color', 'red');
```

### Vector Fields

Vector fields enable nearest-neighbor similarity search (available in RediSearch v2.2+).
Use `addVectorField()` when defining the schema:

```php-inline
use Ehann\RediSearch\Fields\VectorField;

$bookIndex->addVectorField(
    'embedding',
    VectorField::ALGORITHM_HNSW,  // FLAT or HNSW
    VectorField::TYPE_FLOAT32,    // FLOAT32 or FLOAT64
    128,                          // number of dimensions
    VectorField::DISTANCE_COSINE  // L2, IP, or COSINE
);
```

## Adding Documents

Add an array of field objects:

```php-inline
$bookIndex->add([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
    new NumericField('price', 9.99),
    new GeoField('place', new GeoLocation(-77.0366, 38.8977)),
    new TagField('color', 'red'),
]);
```

Add an associative array:

```php-inline
$bookIndex->add([
    'title' => 'Tale of Two Cities',
    'author' => 'Charles Dickens',
    'price' => 9.99,
    'place' => new GeoLocation(-77.0366, 38.8977),
    'color' => new TagField('color', 'red'),
]);
```

Create a document with the index's makeDocument method, then set field values:

```php-inline
$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->place->setValue(new GeoLocation(-77.0366, 38.8977));
$document->color->setValue(new Tag('red'));

$bookIndex->add($document);
```

DocBlocks can (optionally) be used to type hint field property names:

```php-inline
/** @var BookDocument $document */
$document = $bookIndex->makeDocument();

// "title" will auto-complete correctly in your IDE provided BookDocument has a "title" property or @property annotation.
$document->title->setValue('How to be awesome.');

$bookIndex->add($document);
```

```php
<?php

namespace Your\Documents;

use Ehann\RediSearch\Document\Document;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TextField;

/**
 * @property TextField title
 * @property TextField author
 * @property NumericField price
 * @property GeoField place
 */
class BookDocument extends Document
{
}
```

## Updating a Document

Documents are updated with an index's replace method.

```php-inline
// Make a document.
$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->place->setValue(new GeoLocation(-77.0366, 38.8977));
$bookIndex->add($document);

// Update a couple fields
$document->title->setValue('How to be awesome: Part 2.');
$document->price->setValue(19.99);

// Update the document.
$bookIndex->replace($document);
```

A document can also be updating when its ID is specified:

```php-inline
// Make a document.
$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->place->setValue(new GeoLocation(-77.0366, 38.8977));
$bookIndex->add($document);

// Create a new document and assign the old document's ID to it.
$newDocument = $bookIndex->makeDocument($document->getId());

// Set a couple fields.
$document->title->setValue('');
$document->author->setValue('Jack');
$newDocument->title->setValue('How to be awesome: Part 2.');
$newDocument->price->setValue(19.99);

// Update the document.
$bookIndex->replace($newDocument);
```

## Batch Indexing

Batch indexing is possible with the **addMany** method.
To index an external collection, make sure to set the document's ID to the ID of the record in the external collection.

```php-inline
// Get a record set from your DB (or some other datastore).
$records = $someDatabase->findAll();

$documents = [];
foreach ($records as $record) {
    // Make a new document with the external record's ID.
    $newDocument = $bookIndex->makeDocument($record->id);
    $newDocument->title->setValue($record->title);
    $newDocument->author->setValue($record->author);
    $documents[] = $newDocument;
}

// Add all the documents at once.
$bookIndex->addMany($documents);

// It is possible to increase indexing speed by disabling atomicity by passing true as the second parameter.
// Note that this is only possible when using the phpredis extension.
$bookIndex->addMany($documents, true);
```

## Document Storage in v2

In RediSearch v2, all documents are stored as Redis hashes (key/value pairs) internally.
The library writes every document via `HSET` — there is no separate indexing step.

`addHash()` and `replaceHash()` are available as aliases for `add()` and `replace()` respectively,
both using upsert semantics:

```php-inline
$document = $bookIndex->makeDocument('foo');
$document->title->setValue('How to be awesome.');
$bookIndex->addHash($document);   // upsert (same as add/replace)
$bookIndex->replaceHash($document); // also upsert
```

## Key Prefixes

You can configure one or more key prefixes so RediSearch only indexes hashes whose Redis key
starts with a given string. Multiple prefixes are treated as **alternatives** — each is a
separate key namespace, not a compound path.

When writing documents, the library always uses the **first** configured prefix to build the
hash key. Each prefix must include its own separator character (e.g. `'post:'`, not `'post'`).

```php-inline
// Index covers keys starting with 'post:' OR 'blog:'
$index->setPrefixes(['post:', 'blog:'])->create();

// Documents are written under the first prefix: 'post:{id}'
$index->add($document);
```

## Index Creation Options

Several `FT.CREATE` options can be set before calling `create()`:

```php-inline
$index
    ->setIndexType('HASH')       // 'HASH' (default) or 'JSON' (requires RedisJSON)
    ->setFilter('@price > 0')    // only index documents matching this expression
    ->setMaxTextFields()         // allow more than 32 TEXT fields
    ->setTemporary(3600)         // auto-expire index after 3600 seconds of inactivity
    ->setSkipInitialScan()       // don't scan existing keys on creation
    ->addTextField('title')
    ->addNumericField('price')
    ->create();
```

## Schema Expansion

Fields can be added to an existing index without recreating it using `alter()`.
Note that existing documents are not retroactively re-indexed for new fields;
only newly added or updated documents will include them.

```php-inline
use Ehann\RediSearch\Fields\NumericField;

$bookIndex->alter(new NumericField('year'));
```

## Listing Indexes

All index names in the current Redis instance can be retrieved:

```php-inline
$names = $bookIndex->listIndexes(); // e.g. ['bookIndex', 'authorIndex']
```

## Aliasing

Indexes can be aliased.

Note that an exception will be thrown if any alias method is called before an index's [schema](/#create-the-schema) is created.

### Adding an Alias

An alias can be added for an index like this:

```php-inline
$index->addAlias('foo');
```

### Updating an Alias

Assuming an alias has already been added to an index, like this:

```php-inline
$oldIndex->addAlias('foo');
```

...it can be reassigned to a different index like this:

```php-inline
$newIndex->updateAlias('foo');
```

### Deleting an Alias

An alias can be deleted like this:

```php-inline
$index->deleteAlias('foo');
```

## Managing an Index

Whether or not an index exists can be checked:

```php-inline
$indexExists = $index->exists();
```

An index can be removed:

```php-inline
$index->drop();
```

Passing `true` also deletes all underlying document hashes from Redis:

```php-inline
$index->drop(true);
```
