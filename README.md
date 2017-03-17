# Redisearch-PHP

## Install

```
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
$bookIndex = new BookIndex();
$bookIndex->create();
```

Add a document to the index...

```php
$bookIndex->addDocument([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
]);
```

Search the index...

```php
$result - $bookIndex->search('two cities');

$result->count();     // Number of documents.
$result->documents(); // Array of matches.

// Documents are returned as objects by default.
$firstResult = $result->documents()[0];
$firstResult->title;
$firstResult->author;
```