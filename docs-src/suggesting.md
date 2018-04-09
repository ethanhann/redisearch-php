# Suggesting

## Creating a Suggestion Index

Create a suggestion index called "MySuggestions"...

```php-inline
use Ehann\RediSearch\Suggestion;

$suggestion = new Suggestion($redisClient, 'MySuggestions');

```

## Adding a Suggestion

Add a suggestion with a score...

```php-inline
$suggestion->add('Tale of Two Cities', 1.10);
```

## Getting a Suggestion

Pass a partial string to the get method... 

```php-inline
$result = $suggestion->get('Cities');
```

## Getting a Suggestion

Pass the entire suggestion string to the delete method... 

```php-inline
$result = $suggestion->delete('Tale of Two Cities');
```


## Getting the Number of Possible Suggestions

Simply use the suggestion index's length method... 

```php-inline
$numberOfPossibleSuggestions = $suggestion->length();
```

