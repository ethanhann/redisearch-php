# Quick Start

Redisearch-PHP is a PHP client library for the [Redisearch](http://redisearch.io/) module which adds Full-Text search to Redis.

## Requirements

* Redis running with the [Redisearch module loaded](http://redisearch.io/Quick_Start/).
* PHP >=7
* The [PhpRedis](https://github.com/phpredis/phpredis) PHP extension.

## Install

```bash
composer install ethanhann/redisearch-php
```

## Create an Index Class

```php
<?php

namespace Your\Indexes;

use Eeh\Redisearch\AbstractIndex;

class BookIndex extends AbstractIndex
{
}
```

## Define the Index's Schema

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

## Create the Underlying Index in Redis

```php
<?php

$bookIndex = new BookIndex();
$bookIndex->create();
```

## Add a Document to the Index

```php
<?php

$bookIndex->addDocument([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
    new NumericField('price', 9.99),
    new NumericField('stock', 231),
]);
```

## Search the Index

```php
<?php

$result = $bookIndex->search('two cities');

$result->count();     // Number of documents.
$result->documents(); // Array of matches.

// Documents are returned as objects by default.
$firstResult = $result->documents()[0];
$firstResult->title;
$firstResult->author;
```

