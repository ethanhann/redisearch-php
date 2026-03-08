# Unit Test Skill

Guide for writing, running, and debugging unit tests in redisearch-php.

## Test Infrastructure

- **Framework**: PHPUnit 9 (`vendor/bin/phpunit`)
- **Config**: `phpunit.xml` (sets Redis connection, default client library)
- **Base class**: `Ehann\Tests\RediSearchTestCase` in `tests/RediSearchTestCase.php`
- **Redis**: must be running on `localhost:6381` (start with `docker compose up -d`)

## Writing a Test

### File location and naming

Mirror the source path under `tests/RediSearch/`:

| Source file | Test file |
|---|---|
| `src/Fields/NumericField.php` | `tests/RediSearch/Fields/NumericFieldTest.php` |
| `src/Aggregate/Reducers/Avg.php` | `tests/RediSearch/Aggregate/Reducers/AvgTest.php` |

### Minimal test class

```php
<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\Tests\RediSearchTestCase;
use Ehann\RediSearch\Fields\NumericField;

class NumericFieldTest extends RediSearchTestCase
{
    private NumericField $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->field = new NumericField('price');
    }

    public function testGetName(): void
    {
        $this->assertEquals('price', $this->field->getName());
    }
}
```

### Using the index in tests

`RediSearchTestCase` provides `$this->redisClient` and `$this->indexName`. Use the stubs for a pre-configured index:

```php
use Ehann\Tests\Stubs\TestIndex;

protected function setUp(): void
{
    parent::setUp();
    $index = new TestIndex($this->redisClient, $this->indexName);
    $index->create();
    // add documents, run queries…
}

protected function tearDown(): void
{
    // RediSearchTestCase::tearDown() calls flushAll() automatically
    parent::tearDown();
}
```

### Testing against a specific Redis client

The `REDIS_LIBRARY` env var selects the client. You can skip a test when a client is not in use:

```php
public function testSomethingPhpRedisOnly(): void
{
    if (!$this->isUsingPhpRedis()) {
        $this->markTestSkipped('PhpRedis only');
    }
    // …
}
```

## Running Tests

```bash
# Start Redis
docker compose up -d

# All tests (Predis, the default)
vendor/bin/phpunit

# Or via Robo
vendor/bin/robo test

# Single test file
vendor/bin/phpunit tests/RediSearch/Fields/NumericFieldTest.php

# Single test method
vendor/bin/phpunit --filter testGetName tests/RediSearch/Fields/NumericFieldTest.php

# Group
vendor/bin/phpunit --group aggregate

# All clients
vendor/bin/robo test:all
```

## Debugging Failures

1. **Connection refused**: Redis isn't running — `docker compose up -d`
2. **Command not found (FT.*)**: Redis Stack module not loaded — ensure you're using the `redis/redis-stack` or `redis/redis-stack-server` image, not plain Redis
3. **Index already exists**: a previous test run didn't clean up — `vendor/bin/phpunit` runs `tearDown` which calls `flushAll`; if interrupted, run `redis-cli -p 6381 flushall` manually
4. **Style errors in test files**: run `vendor/bin/php-cs-fixer fix tests --dry-run --diff` to see what needs fixing (php-cs-fixer only auto-fixes `src/` by default, but the check applies to `tests/` too)

## Test Conventions

- Method names: `test{Feature}` (e.g., `testSortByDescending`, `testGetAverageOfNumeric`)
- One logical assertion per test where practical; use `assertSame` over `assertEquals` when type matters
- Group related tests with `@group <name>` PHPDoc annotation
- Do not commit tests that are skipped permanently — remove them or fix the underlying issue
