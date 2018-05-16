# Aggregating

## The Basics

Make an [index](indexing.md) and add a few documents to it: 

```php-inline
use Ehann\RediSearch\Index;

$bookIndex = new Index($redis);

$bookIndex->add([
    'title' => 'How to be awesome',
    'price' => 9.99
]);

$bookIndex->add([
    'title' => 'Aggregating is awesome',
    'price' => 19.99
]);
```

Now group by title and get the average price:

```php-inline
$results = $bookIndex->makeAggregateBuilder()
    ->groupBy('title')
    ->avg('price');
```
