# Indexing

## Array of Field Objects
As shown in the quick start, an array of field objects can be added to the index.  

```php
<?php

$bookIndex->addDocument([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
    new NumericField('price', 9.99),
    new NumericField('stock', 231),
]);
```

## Associative Array
Aside from adding an array of fields, an associative array can be used to add a document.

```php
<?php

$bookIndex->addDocument([
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

$this->addDocument($document);
```

DocBlocks can optionally be used to type hint the magic methods . 

```php
<?php

namespace Your\Documents;

use Eeh\Redisearch\Document;
use Eeh\Redisearch\Fields\FieldInterface;

/**
 * @property FieldInterface title
 * @property FieldInterface author
 * @property FieldInterface price
 * @property FieldInterface stock
 */
class BookDocument extends Document
{
}
```

```php
<?php

/** @var BookDocument $document */
$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->stock->setValue(231);

$this->addDocument($document);
```
