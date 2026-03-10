<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentDeleteCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('document:delete')
            ->setDescription('Delete a document from a RediSearch index')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name')
            ->addArgument('id', InputArgument::REQUIRED, 'Document ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('index');
        $docId = $input->getArgument('id');

        $index = $this->createIndex($input, $indexName);
        $deleted = $index->delete($docId);

        if ($deleted) {
            $output->writeln("Document '$docId' deleted from index '$indexName'.");
        } else {
            $output->writeln("<error>Document '$docId' not found.</error>");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
