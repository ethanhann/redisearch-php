<?php

namespace Ehann\RediSearch\Redis;

use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Exceptions\UnknownRediSearchCommandException;
use Ehann\RediSearch\Exceptions\UnsupportedLanguageException;
use Ehann\RediSearch\Exceptions\UnsupportedRedisDatabaseException;
use Psr\Log\LoggerInterface;

abstract class AbstractRedisClient implements RedisClientInterface
{
    protected $redis;
    /** @var  LoggerInterface */
    protected $logger;

    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisClientInterface
    {
        return $this;
    }

    public function flushAll()
    {
        $this->redis->flushAll();
    }

    public function multi(bool $usePipeline = false)
    {}

    public function rawCommand(string $command, array $arguments)
    {}

    public function prepareRawCommandArguments(string $command, array $arguments) : array
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
        return $arguments;
    }

    /**
     * @param $rawResult
     * @return mixed
     * @throws UnknownIndexNameException
     * @throws UnknownRediSearchCommandException
     * @throws UnsupportedLanguageException
     * @throws UnsupportedRedisDatabaseException
     */
    public function validateRawCommandResults($rawResult)
    {
        $this->throwExceptionIfRawResultIndicatesAnError($rawResult);
        return $rawResult;
    }

    /**
     * @param $rawResult
     * @throws UnknownIndexNameException
     * @throws UnknownRediSearchCommandException
     * @throws UnsupportedLanguageException
     * @throws UnsupportedRedisDatabaseException
     */
    public function throwExceptionIfRawResultIndicatesAnError($rawResult)
    {
        if (!is_string($rawResult)) {
            return;
        }
        if ($rawResult === 'Cannot create index on db != 0') {
            throw new UnsupportedRedisDatabaseException();
        }
        if ($rawResult === 'Unknown Index name') {
            throw new UnknownIndexNameException();
        }
        if (in_array($rawResult, ['Unsupported Language', 'Unsupported Stemmer Language'])) {
            throw new UnsupportedLanguageException();
        }
        if (strpos($rawResult, 'ERR unknown command \'FT.') !== false) {
            throw new UnknownRediSearchCommandException($rawResult);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return RedisClientInterface
     */
    public function setLogger(LoggerInterface $logger): RedisClientInterface
    {
        $this->logger = $logger;
        return $this;
    }
}
