# Indexing

## Array of Field Objects
As shown in the quick start, an array of field objects can be added to the index.  

```php
<?php

$bookIndex->add([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
    new NumericField('price', 9.99),
    new NumericField('stock', 231),
]);
```

## Associative Array
Aside from adding an array of fields, an associative array can be used to index a document.

```php
<?php

$bookIndex->add([
    'title' => 'Tale of Two Cities',
    'author' => 'Charles Dickens',
    'price' => 9.99,
    'stock' => 231,
]);
```

## Document Factory and Magic Methods

Another way is to use the index to make a document, set values using magic methods, then add it.

```php
<?php

$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->stock->setValue(231);

$this->add($document);
```

DocBlocks can optionally be used to type hint the magic methods. 

```php
<?php

/** @var BookDocument $document */
$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->stock->setValue(231);

$this->add($document);
```

```php
<?php

namespace Your\Documents;

use Eeh\Redisearch\Document\Document;
use Eeh\Redisearch\Fields\NumericField;
use Eeh\Redisearch\Fields\TextField;

/**
 * @property TextField title
 * @property TextField author
 * @property NumericField price
 * @property NumericField stock
 */
class BookDocument extends Document
{
}
```

## Replace/Update a Document

```php
<?php

$bookIndex->replace([
    'title' => 'Tale of Two Cities',
    'author' => 'Charles Dickens',
    'price' => 9.99,
    'stock' => 231,
]);
```
