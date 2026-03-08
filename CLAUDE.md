# CLAUDE.md

This file provides guidance for Claude Code when working in this repository.

## Project Overview

`redisearch-php` is a PHP client library for the RediSearch module. It wraps the Redis client adapters (Predis, PhpRedis, RedisClient) behind a unified interface and exposes RediSearch commands as fluent PHP objects.

## Development Setup

### Start Redis

```bash
docker compose up -d
```

This starts `redis/redis-stack` (includes the RediSearch module) on port **6381**.

### Install Dependencies

```bash
composer install
```

## Running Tests

```bash
# Default (Predis client)
vendor/bin/robo test

# Specific client
vendor/bin/robo test:predis
vendor/bin/robo test:php-redis
vendor/bin/robo test:redis-client

# All clients
vendor/bin/robo test:all

# Run phpunit directly (used by CI)
vendor/bin/phpunit
```

Tests flush the entire Redis database on teardown. Never run against a Redis instance with important data.

## Code Style

```bash
# Fix in place
vendor/bin/robo task:fix-code-style

# Check only (no modifications — used by CI)
vendor/bin/php-cs-fixer fix src --dry-run --diff
```

## Lint

```bash
find src tests -name "*.php" -print0 | xargs -0 php -l
```

## Project Structure

```
src/                  # Ehann\RediSearch\ namespace (PSR-4)
  Aggregate/          # Aggregation pipeline builders
    Operations/
    Reducers/
  Query/              # Query string builders
  Fields/             # Field type definitions
  Document/           # Document abstraction
  Exceptions/
  Index.php           # Primary entry point
tests/                # Ehann\Tests\ namespace (PSR-4)
  RediSearch/         # Mirrors src/ structure
  Stubs/              # Fixtures (TestIndex, etc.)
  RediSearchTestCase.php  # Base test class
```

## Key Conventions

- **Namespaces**: `Ehann\RediSearch\` for source, `Ehann\Tests\` for tests
- **Interfaces**: suffix `Interface` (e.g., `IndexInterface`)
- **Abstract classes**: prefix `Abstract` (e.g., `AbstractIndex`)
- **Test files**: `{ClassName}Test.php`
- **Test methods**: `test{Description}` (e.g., `testGetAverageOfNumeric`)
- **PHP version**: 8.2+, use native type hints throughout

## CI

GitHub Actions (`.github/workflows/ci.yml`) runs two jobs:

1. **lint-and-format** — PHP syntax check + php-cs-fixer dry-run on PHP 8.2
2. **test** — PHPUnit matrix across PHP 8.2 / 8.3 / 8.4 (requires lint to pass first)

## Custom Skills

- `/contribute` — step-by-step guide for adding a new feature or bug fix
- `/unit-test` — guide for writing and running unit tests
