# Searching

## Simple Text Search

Text fields can be filtered with the index's search method.

```php
<?php

$result = $bookIndex->search('two cities');

$result->count();     // Number of documents.
$result->documents(); // Array of stdObjects.
```

Documents can also be returned as arrays instead of objects by passing true as the second parameter to the search method.

```php
<?php

$result = $bookIndex->search('two cities', true);

$result->documents(); // Array of arrays.
```

## Filtering Numeric Fields

Numeric fields can be filtered with the index's filter method.

```php
<?php

$result = $bookIndex
    ->filter('price', 4.99, 19.99)
    ->search('two cities');
```
