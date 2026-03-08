# Contribute Skill

Guide for implementing a new feature or bug fix in redisearch-php.

## Workflow

Make a todo list for all the tasks below and work through them in order.

### 1. Understand the Change

Read the relevant existing code before touching anything:

- For new field types: look at an existing field in `src/Fields/`
- For new query features: look at `src/Query/`
- For new aggregation reducers/operations: look at `src/Aggregate/Reducers/` or `src/Aggregate/Operations/`
- For index-level changes: look at `src/Index.php` and `src/AbstractIndex.php`

Identify the interface(s) the new code must implement or extend.

### 2. Implement the Change

**Source code conventions:**
- Namespace: `Ehann\RediSearch\<Subdirectory>`
- Use native PHP 8.2+ type hints on all parameters and return types
- Interfaces end in `Interface`; abstract classes start with `Abstract`
- Follow the fluent builder pattern used throughout (methods return `$this` or a new builder)
- Keep RediSearch command names as close to the official docs as possible

**Code style** is enforced by php-cs-fixer. After writing code, fix style:

```bash
vendor/bin/robo task:fix-code-style
```

### 3. Write Tests

Every change needs a corresponding test. See the `/unit-test` skill for details.

Quick checklist:
- Add a test file at `tests/RediSearch/<matching path>/<ClassName>Test.php`
- Extend `Ehann\Tests\RediSearchTestCase`
- Cover the happy path and any notable edge cases

### 4. Verify Everything Passes

```bash
# Start Redis if not running
docker compose up -d

# Run the full test suite
vendor/bin/robo test

# Check code style
vendor/bin/php-cs-fixer fix src --dry-run --diff

# Lint all files
find src tests -name "*.php" -print0 | xargs -0 php -l
```

All three must pass cleanly before the change is ready.

### 5. Optional: Test Against All Clients

If the change touches the Redis command layer, verify it works with every supported client:

```bash
vendor/bin/robo test:all
```

### 6. Commit

Write a clear commit message describing *what* changed and *why*. Reference any related issue numbers.

```bash
git add <files>
git commit -m "Add <feature>: <one-line description>"
```

## Common Patterns

### Adding a new Field type

```php
namespace Ehann\RediSearch\Fields;

class MyField extends AbstractField implements FieldInterface
{
    public function getTypeString(): string
    {
        return 'MYTYPE';
    }
}
```

### Adding a new Reducer

```php
namespace Ehann\RediSearch\Aggregate\Reducers;

class MyReducer extends AbstractReducer
{
    public function __construct(string $property)
    {
        parent::__construct('MY_REDUCER', $property);
    }
}
```

### Adding a new Query Operation

Follow the pattern in `src/Aggregate/Operations/` — implement `OperationInterface` and emit the correct RediSearch command fragment from `__toString()`.
