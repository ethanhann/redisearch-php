# Suggesting

## Creating a Suggestion Index

Create a suggestion index called "MySuggestions"...

```php
<?php

use Ehann\RediSearch\Suggestion;

$suggestion = new Suggestion($redisClient, 'MySuggestions');

```

## Adding a Suggestion

Add a suggestion with a score...

```php
<?php

$suggestion->add('Tale of Two Cities', 1.10);
```

## Getting a Suggestion

Pass a partial string to the get method... 

```php
<?php

$result = $suggestion->get('Cities');
```

## Getting a Suggestion

Pass the entire suggestion string to the delete method... 

```php
<?php

$result = $suggestion->delete('Tale of Two Cities');
```


## Getting the Number of Possible Suggestions

Simply use the suggestion index's length method... 

```php
<?php

$numberOfPossibleSuggestions = $suggestion->length();
```

