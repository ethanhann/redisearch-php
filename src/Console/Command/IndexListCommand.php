<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexListCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('index:list')
            ->setDescription('List all RediSearch indexes')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $this->createIndex($input, '_cli_tmp');
        $indexes = $index->listIndexes();
        $indexes = array_map(fn ($i) => (string) $i, $indexes);

        if ($input->getOption('json')) {
            $this->renderJson($output, $indexes);
            return self::SUCCESS;
        }

        if (empty($indexes)) {
            $output->writeln('No indexes found.');
            return self::SUCCESS;
        }

        $this->renderTable($output, ['Index Name'], array_map(fn ($i) => [$i], $indexes));

        return self::SUCCESS;
    }
}
