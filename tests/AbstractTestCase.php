<?php

namespace Ehann\Tests;

use Ehann\RediSearch\Redis\PhpRedisAdapter;
use Ehann\RediSearch\Redis\PredisAdapter;
use Ehann\RediSearch\Redis\RedisClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    /** @var string */
    protected $indexName;
    /** @var RedisClientInterface */
    protected $redisClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->redisClient = getenv('REDIS_LIBRARY') === 'Predis' ?
            $this->makeRedisClientWithPredis() :
            $this->makeRedisClientWithPhpRedis();

        if (getenv('IS_LOGGING_ENABLED')) {
            $logger = new Logger('Ehann\RediSearch');
            $logger->pushHandler(new StreamHandler(getenv('LOG_FILE'), Logger::DEBUG));
            $this->redisClient->setLogger($logger);
        }
    }

    protected function makeRedisClientWithPhpRedis(): RedisClientInterface
    {
        return (new PhpRedisAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }

    protected function makeRedisClientWithPredis(): RedisClientInterface
    {
        return (new PredisAdapter())->connect(
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
    }
}
