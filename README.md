# Quick Start

Redisearch-PHP is a PHP client library for the [Redisearch](http://redisearch.io/) module which adds Full-Text search to Redis.

## Requirements

* Redis running with the [Redisearch module loaded](http://redisearch.io/Quick_Start/).
* PHP >=7
* [PhpRedis](https://github.com/phpredis/phpredis) OR [Predis](https://github.com/nrk/predis).

## Install

```bash
composer install ethanhann/redisearch-php
```

## Load

```php
<?php

require_once 'vendor/autoload.php';
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

## Define the Index's Schema and Create the Underlying Index in Redis

```php
<?php

$bookIndex = new BookIndex();

$bookIndex->addTextField('title')
    ->addTextField('author')
    ->addNumericField('price')
    ->addNumericField('stock')
    ->create();
```

## Add a Document to the Index

```php
<?php

$bookIndex->add([
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

