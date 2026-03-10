<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExplainCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('explain')
            ->setDescription('Show the execution plan for a query (FT.EXPLAIN)')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name')
            ->addArgument('query', InputArgument::REQUIRED, 'Search query');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('index');
        $query = $input->getArgument('query');

        $index = $this->createIndex($input, $indexName);
        $explanation = $index->explain($query);

        $output->writeln($explanation);

        return self::SUCCESS;
    }
}
