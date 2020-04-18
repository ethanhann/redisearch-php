# Indexing

## Field Types

There are four types of fields that can be added to a document: **TextField**, **NumericField**, **GeoField** and **TagField**.

They are instantiated like this:

```php-inline
new TextField('author', 'Charles Dickens');
new NumericField('price', 9.99);
new GeoField('place', new GeoLocation(-77.0366, 38.8977));
new TagField('color', 'red');
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
    'color' => new TagField('color', 'red'),,
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

$this->add($document);
```

DocBlocks can (optionally) be used to type hint field property names:

```php-inline
/** @var BookDocument $document */
$document = $bookIndex->makeDocument();

// "title" will auto-complete correctly in your IDE provided BookDocument has a "title" property or @property annotation.
$document->title->setValue('How to be awesome.');

$this->add($document);
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

## Indexing From a Hash

Redis hashes are key/value pairs referenced by a key. 
It is possible to index an existing hash with the **addHash** method.
The document's ID has to be the same as the hash's key.
Attempting to index a hash that does not exist will result in an exception.

Index a hash with the key "foo":

```php
$document = $bookIndex->makeDocument('foo');
$bookIndex->addHash($document);
```

Replace a document in the index from a hash:

```php
$document = $bookIndex->makeDocument('foo');
$bookIndex->replaceHash($document);
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
