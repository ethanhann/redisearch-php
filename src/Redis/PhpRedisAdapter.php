<?php

namespace Ehann\RediSearch\Redis;

class PhpRedisAdapter extends AbstractRedisClient
{
    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisClientInterface
    {
        $this->redis = new \Redis();
        $this->redis->connect($hostname, $port);
        $this->redis->select($db);
        $this->redis->auth($password);
        return $this;
    }

    public function multi(bool $usePipeline = false)
    {
        return $this->redis->multi($usePipeline ? \Redis::PIPELINE : \Redis::MULTI);
    }

    public function rawCommand(string $command, array $arguments)
    {
        $this->prepareRawCommandArguments($command, $arguments);
        $rawResult = call_user_func_array([$this->redis, 'rawCommand'], $arguments);
        return $this->validateRawCommandResults($rawResult);
    }
}
