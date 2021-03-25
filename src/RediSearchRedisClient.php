<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Exceptions\AliasDoesNotExistException;
use Ehann\RediSearch\Exceptions\DocumentAlreadyInIndexException;
use Ehann\RediSearch\Exceptions\RediSearchException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameOrNameIsAnAliasItselfException;
use Ehann\RediSearch\Exceptions\UnknownRediSearchCommandException;
use Ehann\RediSearch\Exceptions\UnsupportedRediSearchLanguageException;
use Ehann\RedisRaw\AbstractRedisRawClient;
use Ehann\RedisRaw\Exceptions\RawCommandErrorException;
use Ehann\RedisRaw\RedisRawClientInterface;
use Exception;
use Psr\Log\LoggerInterface;

class RediSearchRedisClient implements RedisRawClientInterface
{
    /** @var AbstractRedisRawClient */
    protected $redis;

    public function __construct(RedisRawClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function validateRawCommandResults($payload, string $command, array $arguments)
    {
        $isPayloadException = $payload instanceof Exception;
        $message = $isPayloadException ? $payload->getMessage() : $payload;

        if (!is_string($message)) {
            return;
        }

        $message = strtolower($message);

        if ($message === 'unknown index name') {
            throw new UnknownIndexNameException();
        }

        if (in_array($message, ['no such language', 'unsupported language', 'unsupported stemmer language', 'bad argument for `language`'])) {
            throw new UnsupportedRediSearchLanguageException();
        }

        if ($message === 'unknown index name (or name is an alias itself)') {
            throw new UnknownIndexNameOrNameIsAnAliasItselfException();
        }

        if ($message === 'alias does not exist') {
            throw new AliasDoesNotExistException();
        }

        if (strpos($message, 'err unknown command \'ft.') !== false) {
            throw new UnknownRediSearchCommandException($message);
        }

        if (strpos($message, 'document already in index') !== false) {
            throw new DocumentAlreadyInIndexException($arguments[0], $arguments[1]);
        }

        throw new RediSearchException($payload);
    }

    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisRawClientInterface
    {
        $this->redis->connect($hostname, $port, $db, $password);
    }

    public function flushAll()
    {
        $this->redis->flushAll();
    }

    public function multi(bool $usePipeline = false)
    {
        return $this->redis->multi($usePipeline);
    }

    public function rawCommand(string $command, array $arguments = [])
    {
        try {
            foreach ($arguments as $index => $value) {
                /* The various RedisRaw clients have different expectations about arg types, but generally they all
                 * agree that they can be strings.
                 */
                $arguments[$index] = strval($value);
            }
            $result = $this->redis->rawCommand($command, $arguments);
        } catch (RawCommandErrorException $exception) {
            $result = $exception->getPrevious()->getMessage();
        }

        if ($command !== 'FT.EXPLAIN') {
            $this->validateRawCommandResults($result, $command, $arguments);
        }

        return $result;
    }

    public function setLogger(LoggerInterface $logger): RedisRawClientInterface
    {
        return $this->redis->setLogger($logger);
    }

    public function prepareRawCommandArguments(string $command, array $arguments): array
    {
        return $this->prepareRawCommandArguments($command, $arguments);
    }
}
