# Default: run tests with Predis
default: test

# Start Redis (detached)
up:
    docker compose up -d

# Install dependencies
install:
    composer install

# Run tests with default client (Predis)
test:
    vendor/bin/phpunit

# Run tests with specific clients
test-predis:
    REDIS_LIBRARY=Predis vendor/bin/phpunit

test-php-redis:
    REDIS_LIBRARY=PhpRedis vendor/bin/phpunit

test-redis-client:
    REDIS_LIBRARY=RedisClient vendor/bin/phpunit

# Run tests with all three clients sequentially
test-all: test-predis test-php-redis test-redis-client

# Fix code style in-place
fmt:
    vendor/bin/php-cs-fixer fix src

# Check code style without modifying (used by CI)
lint-fmt:
    vendor/bin/php-cs-fixer fix src --dry-run --diff

# Lint PHP syntax
lint:
    find src tests -name "*.php" -print0 | xargs -0 php -l

# Full build: fmt then test
build: fmt test-all
