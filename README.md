## What is this?

Redisearch-PHP is a PHP client library for the [Redisearch module](http://redisearch.io/) which adds Full-Text search to Redis.

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

class BookIndex extends AbstractIndex
{
    public $title;
    public $author;

    public function __construct()
    {
        $this->title = new TextField('title');
        $this->author = new TextField('author');
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
]);
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