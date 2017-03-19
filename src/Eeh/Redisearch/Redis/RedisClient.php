<?php

namespace Eeh\Redisearch\Redis;


use Eeh\Redisearch\Exceptions\InvalidRedisClientClassException;

class RedisClient
{
    private $redis;

    public function setRedis($redis): RedisClient
    {
        if (!in_array(get_class($redis), ['Redis', 'Predis\Client'])) {
            throw new InvalidRedisClientClassException();
        }
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
        return get_class($this->redis) === 'Predis\Client' ?
            $this->redis->executeRaw($arguments) :
            call_user_func_array([$this->redis, 'rawCommand'], $arguments);
    }
}
