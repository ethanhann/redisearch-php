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

### Define Searchable Schema

Define the field types that will be used on indexing

```php
<?php

namespace App;

use Laravel\Scout\Searchable;
...
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\Fields\GeoField;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TagField;
use Ehann\RediSearch\Fields\GeoLocation;
...

class User extends Model {
    use Searchable;

    public function searchableAs()
    {
        return "user_index";
    }

    public function toSearchableArray()
    {
        return [
            "name" => $this->name,
            "username" => $this->username,
            "location" => new GeoLocation(
                                $this->longitude,
                                $this->latitude
                            )
            "age" => $this->age,
       ];
    }

    public function searchableSchema()
    {
        return [
            "name" => TextField::class,
            "username" => TextField::class,
            "location" => GeoField::class,
            "age" => NumericField::class
      ];
    }
}
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

### Query Filters

How To Query Filters? [Filtering Tag Fields](http://www.ethanhann.com/redisearch-php/searching/#filtering-tag-fields)

```php
App\User::search("Search Query", function($index){
    return $filter->geoFilter("location", 5.56475, 5.75516, 100)
                  ->numericFilter('age', 18, 32)
})->get()
```

## What now?

See the [Laravel Scout](https://laravel.com/docs/5.6/scout) documentation for additional information.
