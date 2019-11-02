<?php

namespace Ehann\RediSearch;

use Ehann\RedisRaw\RedisRawClientInterface;

abstract class AbstractRediSearchClientAdapter
{
    /** @var RediSearchRedisClient */
    protected $redisClient;

    public function __construct(RedisRawClientInterface $redisClient = null)
    {
        $this->redisClient = new RediSearchRedisClient($redisClient);
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return mixed
     */
    protected function rawCommand(string $command, array $arguments)
    {
        return $this->redisClient->rawCommand($command, $arguments);
    }
}
