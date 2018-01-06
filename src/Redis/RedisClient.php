<?php

namespace Ehann\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\InvalidRedisClientClassException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Exceptions\UnsupportedLanguageException;
use Psr\Log\LoggerInterface;

class RedisClient
{
    private $redis;
    /** @var  LoggerInterface */
    private $logger;

    public function __construct($redis = 'Redis', $hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null)
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
        } elseif (is_object($redis) && in_array(get_class($redis), ['Redis', 'Predis\Client'])) {
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
            $this->redis->pipeline() :
            $this->redis->multi($usePipelineForPhpRedis ? \Redis::PIPELINE : \Redis::MULTI);
    }

    public function rawCommand(string $command, array $arguments)
    {
        foreach ($arguments as $index => $argument) {
            if (!is_scalar($arguments[$index])) {
                $arguments[$index] = (string)$argument;
            }
        }
        array_unshift($arguments, $command);
        if ($this->logger) {
            $this->logger->debug(implode(' ', $arguments));
        }
        $rawResult = $this->isPredisClient() ?
            $this->redis->executeRaw($arguments) :
            call_user_func_array([$this->redis, 'rawCommand'], $arguments);

        if ($rawResult === 'Unknown Index name') {
            throw new UnknownIndexNameException();
        }
        $unsupportedLanguageMessages = ['Unsupported Language', 'Unsupported Stemmer Language'];
        if (in_array($rawResult, $unsupportedLanguageMessages)) {
            throw new UnsupportedLanguageException();
        }

        return $rawResult;
    }

    public function setLogger(LoggerInterface $logger): RedisClient
    {
        $this->logger = $logger;
        return $this;
    }
}
