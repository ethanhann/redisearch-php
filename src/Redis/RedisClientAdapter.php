<?php

namespace Ehann\RediSearch\Redis;

use RedisClient\RedisClient;
use RedisClient\Exception\ErrorResponseException;

/**
 * Class RedisClientAdapter
 * @package Ehann\RediSearch\Redis
 *
 * This class wraps the Cheprasov client: https://github.com/cheprasov/php-redis-client
 */
class RedisClientAdapter extends AbstractRedisClient
{
    /** @var RedisClient */
    public $redis;

    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisClientInterface
    {
        $this->redis = new RedisClient([
            'server' => "$hostname:$port",
            'database' => $db,
            'password' => $password,
        ]);

        return $this;
    }

    public function multi(bool $usePipeline = false)
    {
        return $this->redis->pipeline();
    }

    public function rawCommand(string $command, array $arguments)
    {
        $arguments = $this->prepareRawCommandArguments($command, $arguments);
        $rawResult = null;
        try {
            $rawResult = $this->redis->executeRaw($arguments);
        } catch (ErrorResponseException $exception) {
            $this->validateRawCommandResults($exception);
        }
        return $this->normalizeRawCommandResult($rawResult);
    }
}
