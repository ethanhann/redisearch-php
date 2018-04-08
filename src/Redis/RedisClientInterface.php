<?php

namespace Ehann\RediSearch\Redis;

use Psr\Log\LoggerInterface;

interface RedisClientInterface
{
    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisClientInterface;
    public function flushAll();
    public function multi(bool $usePipeline = false);
    public function rawCommand(string $command, array $arguments);
    public function setLogger(LoggerInterface $logger): RedisClientInterface;
    public function prepareRawCommandArguments(string $command, array $arguments) : array;
    public function validateRawCommandResults($rawResult);
}
