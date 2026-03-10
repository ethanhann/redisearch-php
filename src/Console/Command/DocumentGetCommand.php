<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentGetCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('document:get')
            ->setDescription('Get a document by ID from Redis')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name (used for connection context)')
            ->addArgument('id', InputArgument::REQUIRED, 'Document ID (full Redis key)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $docId = $input->getArgument('id');
        $client = $this->createClient($input);

        $result = $client->rawCommand('HGETALL', [$docId]);

        if (empty($result)) {
            $output->writeln("<error>Document '$docId' not found.</error>");
            return self::FAILURE;
        }

        $data = $this->parseHashResult($result);

        if ($input->getOption('json')) {
            $this->renderJson($output, $data);
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($data as $field => $value) {
            $rows[] = [$field, (string) $value];
        }

        $this->renderTable($output, ['Field', 'Value'], $rows);

        return self::SUCCESS;
    }

    private function parseHashResult(array $result): array
    {
        if (!array_is_list($result)) {
            return array_map(fn ($v) => (string) $v, $result);
        }

        $data = [];
        for ($i = 0; $i < count($result) - 1; $i += 2) {
            $data[(string) $result[$i]] = (string) $result[$i + 1];
        }

        return $data;
    }
}
