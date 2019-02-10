# Searching

## Simple Text Search

Text fields can be filtered with the index's search method.

```php-inline
$result = $bookIndex->search('two cities');

$result->count();     // Number of documents.
$result->documents(); // Array of stdObjects.
```

Documents can also be returned as arrays instead of objects by passing true as the second parameter to the search method.

```php-inline
$result = $bookIndex->search('two cities', true);

$result->documents(); // Array of arrays.
```

## Filtering Numeric Fields

Numeric fields can be filtered with the index's filter method.

```php-inline
$result = $bookIndex
    ->filter('price', 4.99, 19.99)
    ->search('two cities');
```

## Filtering Geo Fields

Numeric fields can be filtered with the index's filter method.

```php-inline
$result = $bookIndex
    ->geoFilter('place', -77.0366, 38.897, 100)
    ->search('two cities');
```

## Sorting Results

Search results can be sorted with the index's sort method.

```php-inline
$result = $bookIndex
    ->sortBy('price')
    ->search('two cities');
```

## Setting a Language

A supported language can be specified when running a query.
Supported languages are represented as constants in the **Ehann\RediSearch\Language** class.  

```php-inline
$result = $bookIndex
    ->language(Language::ITALIAN)
    ->search('two cities');
```

## Explaining a Query

An explanation for a query can be generated with the index's explain method.

This can be helpful for understanding why a query is returning a set of results.

```php-inline
$result = $bookIndex
    ->filter('price', 4.99, 19.99)
    ->sortBy('price')
    ->explain('two cities');
```

## Logging Queries

Logging is optional. It can be enabled by injecting a PSR compliant logger, such as Monolog, into a RedisClient instance.

Install Monolog:

```bash
composer require monolog/monolog
```

Inject a logger instance (with a stream handler in this example):

```php-inline
$logger = new Logger('Ehann\RediSearch');
$logger->pushHandler(new StreamHandler('MyLogFile.log', Logger::DEBUG));
$this->redisClient->setLogger($logger);
```
