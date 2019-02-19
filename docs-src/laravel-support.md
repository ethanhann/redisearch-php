# Laravel Support

[Laravel-RediSearch](https://github.com/ethanhann/Laravel-RediSearch) allows for indexing and searching Laravel models.
It provides a [Laravel Scout](https://laravel.com/docs/5.6/scout) driver.

## Getting Started

### Install

```bash
composer require ethanhann/laravel-redisearch
```

###  Register the Provider

Add this entry to the providers array in config/app.php.

```php-inline
Ehann\LaravelRediSearch\RediSearchServiceProvider::class
```

### Configure the Scout Driver

Update the Scout driver in config/scout.php.

```php-inline
'driver' => env('SCOUT_DRIVER', 'ehann-redisearch'),
```

### Import a Model

Import a "Product" model that is [configured to be searchable](https://laravel.com/docs/5.6/scout#configuration):

```bash
artisan ehann:redisearch:import App\\Product
```

Delete the index before importing:

```bash
artisan ehann:redisearch:import App\\Product --recreate-index
```

Import models without an ID field (this should be rarely needed):

```bash
artisan ehann:redisearch:import App\\Product --no-id
```

## What now?

See the [Laravel Scout](https://laravel.com/docs/5.6/scout) documentation for additional information. 

