<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexInfoCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('index:info')
            ->setDescription('Show information about a RediSearch index')
            ->addArgument('name', InputArgument::REQUIRED, 'Index name')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('name');
        $index = $this->createIndex($input, $indexName);
        $info = $index->info();

        if ($input->getOption('json')) {
            $this->renderJson($output, $this->normalizeInfo($info));
            return self::SUCCESS;
        }

        $rows = $this->infoToRows($info);
        $this->renderTable($output, ['Property', 'Value'], $rows);

        return self::SUCCESS;
    }

    private function normalizeInfo(mixed $info): array
    {
        if (!is_array($info)) {
            return [];
        }

        if (!array_is_list($info)) {
            return array_map(
                fn ($v) => is_array($v) ? $v : (string) $v,
                $info
            );
        }

        $result = [];
        for ($i = 0; $i < count($info) - 1; $i += 2) {
            $key = (string) $info[$i];
            $result[$key] = is_array($info[$i + 1]) ? $info[$i + 1] : (string) $info[$i + 1];
        }

        return $result;
    }

    private function infoToRows(mixed $info): array
    {
        $normalized = $this->normalizeInfo($info);
        $rows = [];

        foreach ($normalized as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $rows[] = [$key, (string) $value];
        }

        return $rows;
    }
}
