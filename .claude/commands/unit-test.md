# Unit Test Skill

Guide for writing, running, and debugging unit tests in redisearch-php.

## Test Infrastructure

- **Framework**: PHPUnit 11 (`vendor/bin/phpunit`)
- **Config**: `phpunit.xml` (sets Redis connection, default client library, coverage source)
- **Base class**: `Ehann\Tests\RediSearchTestCase` in `tests/RediSearchTestCase.php`
- **Redis**: must be running on `localhost:6381` (start with `just up`)

## Writing a Test

### File location and naming

Mirror the source path under `tests/RediSearch/`:

| Source file | Test file |
|---|---|
| `src/Fields/NumericField.php` | `tests/RediSearch/Fields/NumericFieldTest.php` |
| `src/Aggregate/Reducers/Avg.php` | `tests/RediSearch/Aggregate/Reducers/AvgTest.php` |

### AAA structure

Every test method must follow Arrange-Act-Assert with explicit section comments:

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
        // Arrange — see setUp()

        // Act
        $name = $this->field->getName();

        // Assert
        $this->assertSame('price', $name);
    }

    public function testSetSortable(): void
    {
        // Arrange
        $expected = true;

        // Act
        $result = $this->field->setSortable($expected)->isSortable();

        // Assert
        $this->assertSame($expected, $result);
    }
}
```

**AAA rules:**
- Always include all three section comments, even when one section is trivial.
- If the full arrange is in `setUp()`, use `// Arrange — see setUp()` as a one-liner with no body.
- For exception tests, place `expectException()` in the Assert section (before the act), because PHPUnit registers the expectation before execution:

```php
public function testThrowsWhenIndexHasNoFields(): void
{
    // Arrange
    $index = new IndexWithoutFields($this->redisClient, $this->indexName);

    // Assert
    $this->expectException(NoFieldsInIndexException::class);

    // Act
    $index->create();
}
```

### Quality conventions

- Use `assertSame` instead of `assertEquals` when type identity matters (e.g., comparing ints, floats, or booleans).
- One logical assertion per test where practical; multiple assertions are acceptable when they together verify a single behaviour.
- Mark all test methods `void`: `public function testFoo(): void`.
- Use `@group <name>` PHPDoc to tag logical groups (e.g., `@group aggregate`, `@group query`).
- Do not commit permanently-skipped tests — fix the underlying issue or remove the test.

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

The `REDIS_LIBRARY` env var selects the client. Skip a test when a client is not in use:

```php
public function testSomethingPhpRedisOnly(): void
{
    // Arrange
    if (!$this->isUsingPhpRedis()) {
        $this->markTestSkipped('PhpRedis only');
    }

    // Act
    // …

    // Assert
    // …
}
```

## Running Tests

```bash
# Start Redis
just up

# All tests (Predis, the default)
vendor/bin/phpunit
# or
just test

# Specific client
just test-predis
just test-php-redis
just test-redis-client

# All clients sequentially
just test-all

# Single test file
vendor/bin/phpunit tests/RediSearch/Fields/NumericFieldTest.php

# Single test method
vendor/bin/phpunit --filter testGetName tests/RediSearch/Fields/NumericFieldTest.php

# Group
vendor/bin/phpunit --group aggregate

# With coverage report (requires Xdebug or PCOV driver)
vendor/bin/phpunit --coverage-text
```

## Code Coverage

PHPUnit 11 generates coverage from the `<coverage>` block in `phpunit.xml`. To produce a report:

```bash
# Terminal summary
vendor/bin/phpunit --coverage-text

# HTML report
vendor/bin/phpunit --coverage-html coverage/
```

Coverage requires a driver: install **Xdebug** (`php -m | grep xdebug`) or **PCOV** (`php -m | grep pcov`). If neither is present, PHPUnit will warn and skip coverage collection.

## Debugging Failures

1. **Connection refused**: Redis isn't running — `just up`
2. **Command not found (FT.*)**: Redis Stack module not loaded — ensure you're using the `redis/redis-stack` or `redis/redis-stack-server` image, not plain Redis
3. **Index already exists**: a previous test run didn't clean up — `tearDown` calls `flushAll`; if interrupted, run `redis-cli -p 6381 flushall` manually
4. **Style errors in test files**: run `vendor/bin/php-cs-fixer fix tests --dry-run --diff` to see what needs fixing (php-cs-fixer only auto-fixes `src/` by default, but the check applies to `tests/` too)

## Test Conventions

- Method names: `test{Feature}` (e.g., `testSortByDescending`, `testGetAverageOfNumeric`)
- All three AAA section comments required in every test method
- `assertSame` over `assertEquals` when type matters
- One logical assertion per test where practical
- Group related tests with `@group <name>` PHPDoc annotation
- Do not commit tests that are skipped permanently — remove them or fix the underlying issue
