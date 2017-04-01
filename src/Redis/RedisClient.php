<?php

namespace Ehann\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\InvalidRedisClientClassException;

class RedisClient
{
    private $redis;

    public function __construct($redis = 'Redis', $hostname = '127.0.0.1', $port = null, $db = 0, $password = '')
    {
        if ($redis === 'Redis') {
            $this->redis = new $redis;
            $this->redis->connect($hostname, $port);
            $this->redis->select($db);
            $this->redis->auth($password);
        } elseif ($redis === 'Predis\Client') {
            $this->redis = new $redis([
                'scheme' => 'tcp',
                'host' => $hostname,
                'port' => $port,
                'database' => $db,
                'password' => $password,
            ]);
            $this->redis->connect();
        } elseif (in_array(get_class($redis), ['Redis', 'Predis\Client'])) {
            $this->redis = $redis;
        } else {
            throw new InvalidRedisClientClassException();
        }
    }

    public function flushAll()
    {
        $this->redis->flushAll();
    }

    public function isPredisClient()
    {
        return get_class($this->redis) === 'Predis\Client';
    }

    public function isPhpRedis()
    {
        return get_class($this->redis) === 'Redis';
    }

    public function multi(bool $usePipelineForPhpRedis = false)
    {
        return $this->isPredisClient() ?
            $this->redis->pipeline()  :
            $this->redis->multi($usePipelineForPhpRedis ? \Redis::PIPELINE : \Redis::MULTI) ;
    }

    public function rawCommand(string $command, array $arguments)
    {
        foreach ($arguments as $index => $argument) {
            if (!is_scalar($arguments[$index])) {
                $arguments[$index] = (string)$argument;
            }
        }
        array_unshift($arguments, $command);
//        print PHP_EOL . implode(' ', $arguments);
        return $this->isPredisClient() ?
            $this->redis->executeRaw($arguments) :
            call_user_func_array([$this->redis, 'rawCommand'], $arguments);
    }
}
