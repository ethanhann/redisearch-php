<?php

namespace Ehann\RediSearch\Console;

use Ehann\RediSearch\Index;
use Ehann\RediSearch\RediSearchRedisClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRedisCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Redis host', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Redis port', 6379)
            ->addOption('password', 'a', InputOption::VALUE_REQUIRED, 'Redis password')
            ->addOption('adapter', null, InputOption::VALUE_REQUIRED, 'Redis adapter (predis, phpredis, redisclient)', 'predis');
    }

    protected function createClient(InputInterface $input): RediSearchRedisClient
    {
        $host = $input->getOption('host');
        $port = (int) $input->getOption('port');
        $password = $input->getOption('password');
        $adapter = strtolower($input->getOption('adapter'));

        try {
            $rawClient = match ($adapter) {
                'predis' => new \Ehann\RedisRaw\PredisAdapter(),
                'phpredis' => new \Ehann\RedisRaw\PhpRedisAdapter(),
                'redisclient' => new \Ehann\RedisRaw\RedisClientAdapter(),
                default => throw new \InvalidArgumentException("Unknown adapter: $adapter. Use predis, phpredis, or redisclient."),
            };
        } catch (\Error $e) {
            $hints = [
                'predis' => 'Install it with: composer require predis/predis',
                'phpredis' => 'Requires the ext-redis PHP extension',
                'redisclient' => 'Install it with: composer require cheprasov/php-redis-client',
            ];
            throw new \RuntimeException(
                "Adapter '$adapter' is not available. " . ($hints[$adapter] ?? $e->getMessage())
            );
        }

        $rawClient->connect($host, $port, 0, $password);

        return new RediSearchRedisClient($rawClient);
    }

    protected function createIndex(InputInterface $input, string $indexName): Index
    {
        $client = $this->createClient($input);

        return (new Index($client, $indexName));
    }

    protected function renderTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }

    protected function renderJson(OutputInterface $output, mixed $data): void
    {
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
