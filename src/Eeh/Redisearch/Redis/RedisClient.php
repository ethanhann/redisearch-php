<?php

namespace Eeh\Redisearch\Redis;

use Redis;
use Predis\Client;

class RedisClient
{
    /** @var Redis */
    private $redis;
    /** @var Client */
    private $predisClient;

    /**
     * @param Redis|Client $redis
     * @return RedisClient
     */
    public function setRedis($redis): RedisClient
    {
        $this->redis = $redis;
        return $this;
    }

    public function flushAll()
    {
        $this->redis->flushAll();
    }

    public function rawCommand(string $command, array $arguments)
    {
//        print PHP_EOL . $command . ' ' . implode(' ', $arguments) . PHP_EOL;
        array_unshift($arguments, $command);
        return $this->redis instanceof Client ?
            $this->redis->executeRaw($arguments) :
            call_user_func_array([$this->redis, 'rawCommand'], $arguments);
    }
}
