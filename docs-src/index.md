# RediSearch-PHP

RediSearch-PHP is a PHP client library for the [RediSearch](http://redisearch.io/) module which adds full-text search to Redis.

## Requirements

* Redis running with the [RediSearch module loaded](http://redisearch.io/Quick_Start/).
* PHP >=7
* [PhpRedis](https://github.com/phpredis/phpredis), [Predis](https://github.com/nrk/predis), or [php-redis-client](https://github.com/cheprasov/php-redis-client).

## Install

```bash
composer require ethanhann/redisearch-php
```

## Load

```php-inline
require_once 'vendor/autoload.php';
```


## Create a Redis Client

```php-inline
use Ehann\RedisRaw\PredisAdapter;
use Ehann\RedisRaw\PhpRedisAdapter;
use Ehann\RedisRaw\RedisClientAdapter;

$redis = (new PredisAdapter())->connect('127.0.0.1', 6379);
// or
$redis = (new PhpRedisAdapter())->connect('127.0.0.1', 6379);
// or
$redis = (new RedisClientAdapter())->connect('127.0.0.1', 6379);

```

## Create the Schema

```php-inline
use Ehann\RediSearch\Index;

$bookIndex = new Index($redis);

$bookIndex->addTextField('title')
    ->addTextField('author')
    ->addNumericField('price')
    ->addNumericField('stock')
    ->create();
```

## Add a Document

```php-inline
$bookIndex->add([
    new TextField('title', 'Tale of Two Cities'),
    new TextField('author', 'Charles Dickens'),
    new NumericField('price', 9.99),
    new NumericField('stock', 231),
]);
```

## Search the Index

```php-inline
$result = $bookIndex->search('two cities');

$result->count();     // Number of documents.
$result->documents(); // Array of matches.

// Documents are returned as objects by default.
$firstResult = $result->documents()[0];
$firstResult->title;
$firstResult->author;
```

