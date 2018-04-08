<?php

namespace Ehann\Tests;

use Ehann\RediSearch\Redis\PhpRedisAdapter;
use Ehann\RediSearch\Redis\PredisAdapter;
use Ehann\RediSearch\Redis\RedisClientAdapter;
use Ehann\RediSearch\Redis\RedisClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    const PREDIS_LIBRARY = 'Predis';
    const PHP_REDIS_LIBRARY = 'PhpRedis';
    const REDIS_CLIENT_LIBRARY = 'RedisClient';

    /** @var string */
    protected $indexName;
    /** @var RedisClientInterface */
    protected $redisClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $factoryMethod = 'make' . getenv('REDIS_LIBRARY') . 'Adapter';
        $this->redisClient = $this->$factoryMethod();

        if (getenv('IS_LOGGING_ENABLED')) {
            $logger = new Logger('Ehann\RediSearch');
            $logger->pushHandler(new StreamHandler(getenv('LOG_FILE'), Logger::DEBUG));
            $this->redisClient->setLogger($logger);
        }
    }

    protected function makePhpRedisAdapter(): RedisClientInterface
    {
        return (new PhpRedisAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function makePredisAdapter(): RedisClientInterface
    {
        return (new PredisAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function makeRedisClientAdapter(): RedisClientInterface
    {
        return (new RedisClientAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function isUsingPredis()
    {
        return getenv('REDIS_LIBRARY') === self::PREDIS_LIBRARY;
    }

    protected function isUsingPhpRedis()
    {
        return getenv('REDIS_LIBRARY') === self::PHP_REDIS_LIBRARY;
    }

    protected function isUsingRedisClient()
    {
        return getenv('REDIS_LIBRARY') === self::REDIS_CLIENT_LIBRARY;
    }
}
