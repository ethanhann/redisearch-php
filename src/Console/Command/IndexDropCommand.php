<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexDropCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('index:drop')
            ->setDescription('Drop a RediSearch index')
            ->addArgument('name', InputArgument::REQUIRED, 'Index name')
            ->addOption('delete-docs', null, InputOption::VALUE_NONE, 'Also delete all indexed documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('name');
        $deleteDocs = $input->getOption('delete-docs');

        $index = $this->createIndex($input, $indexName);
        $index->drop($deleteDocs);

        $output->writeln("Index '$indexName' dropped successfully.");

        return self::SUCCESS;
    }
}
