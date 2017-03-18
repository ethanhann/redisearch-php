## What is this?

Redisearch-PHP is a PHP client library for the [Redisearch](http://redisearch.io/) module which adds Full-Text search to Redis.

## Requirements

* Redis running with the [Redisearch module loaded](http://redisearch.io/Quick_Start/).
* PHP >=7
* The [PhpRedis](https://github.com/phpredis/phpredis) PHP extension.

## Install

```bash
composer install ethanhann/redisearch-php
```

## Basic Usage

Create an index by extending Eeh/Redisearch/AbstractIndex...

```php
<?php

namespace Your\Indexes;

use Eeh\Redisearch\AbstractIndex;

class BookIndex extends AbstractIndex
{
}
```

Add some fields to the index which defines the schema...

```php
<?php

namespace Your\Indexes;

use Eeh\Redisearch\AbstractIndex;
use Eeh\Redisearch\Fields\TextField;
use Eeh\Redisearch\Fields\NumericField;

class BookIndex extends AbstractIndex
{
    public $title;
    public $author;
    public $price;
    public $stock;
    
    public function __construct()
    {
        $this->title = new TextField('title');
        $this->author = new TextField('author');
        $this->price = new NumericField('price');
        $this->stock = new NumericField('stock');
    }
}
```

Create the index...

```php
<?php

$bookIndex = new BookIndex();
$bookIndex->create();
```

Add a document to the index...

```php
<?php

$bookIndex->addDocument([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
    new NumericField('price', 9.99),
    new NumericField('stock', 231),
]);
```

...or add a document with an associative array... 

```php
<?php

$bookIndex->addDocument([
    'title' => 'Tale of Two Cities',
    'author' => 'Charles Dickens',
    'price' => 9.99,
    'stock' => 231,
]);
```

...or use the index to make a document, set values using magic methods, then add it... 

```php
<?php

$document = $bookIndex->makeDocument();
$document->title->setValue('How to be awesome.');
$document->author->setValue('Jack');
$document->price->setValue(9.99);
$document->stock->setValue(231);

$this->addDocument($document);
```

Search the index...

```php
<?php

$result - $bookIndex->search('two cities');

$result->count();     // Number of documents.
$result->documents(); // Array of matches.

// Documents are returned as objects by default.
$firstResult = $result->documents()[0];
$firstResult->title;
$firstResult->author;
```