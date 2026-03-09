<?php

namespace Ehann\Tests;

use Ehann\RediSearch\RediSearchRedisClient;
use Ehann\RedisRaw\AbstractRedisRawClient;
use Ehann\RedisRaw\PhpRedisAdapter;
use Ehann\RedisRaw\PredisAdapter;
use Ehann\RedisRaw\RedisClientAdapter;
use Ehann\RedisRaw\RedisRawClientInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

abstract class RediSearchTestCase extends TestCase
{
    public const PREDIS_LIBRARY = 'Predis';
    public const PHP_REDIS_LIBRARY = 'PhpRedis';
    public const REDIS_CLIENT_LIBRARY = 'RedisClient';

    /** @var string */
    protected $indexName;
    /** @var RediSearchRedisClient */
    protected $redisClient;
    /** @var Logger|null */
    protected $logger;

    public function setUp(): void
    {
        parent::setUp();

        $factoryMethod = 'make' . getenv('REDIS_LIBRARY') . 'Adapter';
        $this->redisClient = new RediSearchRedisClient($this->$factoryMethod());

        if (getenv('IS_LOGGING_ENABLED')) {
            $logger = new Logger('Ehann\RediSearch');
            $handler = new StreamHandler(getenv('LOG_FILE'), Logger::DEBUG);
            $handler->setFormatter(new LineFormatter("%message%\n", null));
            $logger->pushHandler($handler);
            $this->redisClient->setLogger($logger);
            $this->logger = $logger;
        }
    }

    protected function makePhpRedisAdapter(): RedisRawClientInterface
    {
        return (new PhpRedisAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function makePredisAdapter(): RedisRawClientInterface
    {
        return (new PredisAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function makeRedisClientAdapter(): RedisRawClientInterface
    {
        return (new RedisClientAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function isUsingPredis(): bool
    {
        return getenv('REDIS_LIBRARY') === AbstractRedisRawClient::PREDIS_LIBRARY;
    }

    protected function isUsingPhpRedis(): bool
    {
        return getenv('REDIS_LIBRARY') === AbstractRedisRawClient::PHP_REDIS_LIBRARY;
    }

    protected function isUsingRedisClient(): bool
    {
        return getenv('REDIS_LIBRARY') === AbstractRedisRawClient::REDIS_CLIENT_LIBRARY;
    }
}
