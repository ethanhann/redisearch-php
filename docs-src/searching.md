# Searching

## Simple Text Search

Text fields can be filtered with the index's search method.

```php-inline
$result = $bookIndex->search('two cities');

$result->getCount();     // Number of documents.
$result->getDocuments(); // Array of stdObjects.
```

Documents can also be returned as arrays instead of objects by passing true as the second parameter to the search method.

```php-inline
$result = $bookIndex->search('two cities', true);

$result->getDocuments(); // Array of arrays.
```

## Filtering
### Tag Fields

Tag fields can be filtered with the index's tagFilter method.

Specifying multiple tags creates a union of documents.

```php-inline
$result = $bookIndex
    ->tagFilter('color', ['blue', 'red'])
    ->search('two cities');
```

Use multiple separate tagFilter calls to create an intersection of documents.

```php-inline
$result = $bookIndex
    ->tagFilter('color', ['blue'])
    ->tagFilter('color', ['red'])
    ->search('two cities');
```

### Numeric Fields

Numeric fields can be filtered with the index's numericFilter method.

```php-inline
$result = $bookIndex
    ->numericFilter('price', 4.99, 19.99)
    ->search('two cities');
```

### Geo Fields

Geo fields can be filtered with the index's geoFilter method.

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


## Number of Results

The number of documents can be retrieved after performing a search.

```php-inline
$result = $bookIndex->search('two cities');

$result->getCount(); // Number of documents.
```

Alternatively, the number of documents can be queried without returning the documents themselves.
This is useful if you want to check the total number of documents without returning any other data from the Redis server.

```php-inline
$numberOfDocuments = $bookIndex->count('two cities');
```

## Setting a Language

A supported language can be specified when running a query.
Supported languages are represented as constants in the **Ehann\RediSearch\Language** class.

```php-inline
$result = $bookIndex
    ->language(Language::ITALIAN)
    ->search('two cities');
```

## Query Dialect

RediSearch v2.4+ supports multiple query dialects that unlock different syntax features.
Use `dialect()` to select a version (1, 2, or 3):

```php-inline
$result = $bookIndex
    ->dialect(2)
    ->search('two cities');
```

Dialect 2 is required for vector/KNN queries and extended query syntax.

## Vector Search

Vector similarity search allows you to find documents whose vector fields are nearest to a
query vector. This requires a field indexed with `addVectorField()`, dialect 2, and the
`params()` method to pass the query vector as a named parameter.

```php-inline
// Pack your float32 values into a binary string.
$queryVector = pack('f*', 0.1, 0.2, 0.3, /* ... 128 floats total */);

$result = $bookIndex
    ->params(['vec' => $queryVector])
    ->dialect(2)
    ->search('*=>[KNN 5 @embedding $vec]');
```

## Spell Checking

`spellCheck()` returns suggestions for potentially misspelled terms in a query.
The optional second argument sets the maximum edit distance (1–4, default 1).

```php-inline
$suggestions = $bookIndex->spellCheck('helo');      // distance 1
$suggestions = $bookIndex->spellCheck('helo', 2);   // distance 2
```

## Synonyms

Synonym groups let you treat different terms as equivalent during search.

```php-inline
// Register 'book', 'novel', and 'tome' as synonyms.
$bookIndex->synUpdate('group1', 'book', 'novel', 'tome');

// Inspect all synonym mappings for the index.
$map = $bookIndex->synDump();
```

## Explaining a Query

An explanation for a query can be generated with the index's explain method.

This can be helpful for understanding why a query is returning a set of results.

```php-inline
$result = $bookIndex
    ->numericFilter('price', 4.99, 19.99)
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
