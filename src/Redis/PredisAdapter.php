<?php

namespace Ehann\RediSearch\Redis;

use Predis\Client;

class PredisAdapter extends AbstractRedisClient
{
    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisClientInterface
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => $hostname,
            'port' => $port,
            'database' => $db,
            'password' => $password,
        ]);
        $this->redis->connect();
        return $this;
    }

    public function multi(bool $usePipeline = false)
    {
        return $this->redis->pipeline();
    }

    public function rawCommand(string $command, array $arguments)
    {
        $preparedArguments = $this->prepareRawCommandArguments($command, $arguments);
        $rawResult = $this->redis->executeRaw($preparedArguments);
        $this->validateRawCommandResults($rawResult);
        return $this->normalizeRawCommandResult($rawResult);
    }
}
