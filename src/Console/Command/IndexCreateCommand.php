<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Ehann\RediSearch\Console\SchemaParser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCreateCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('index:create')
            ->setDescription('Create a new RediSearch index from a JSON schema')
            ->addArgument('name', InputArgument::REQUIRED, 'Index name')
            ->addArgument('schema-file', InputArgument::REQUIRED, 'Path to JSON schema file')
            ->addOption('on', null, InputOption::VALUE_REQUIRED, 'Index type (HASH or JSON)', 'HASH')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Key prefix(es)')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter expression')
            ->addOption('stopwords', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Stop words')
            ->addOption('maxtextfields', null, InputOption::VALUE_NONE, 'Allow more than 32 text fields')
            ->addOption('temporary', null, InputOption::VALUE_REQUIRED, 'TTL in seconds for temporary index')
            ->addOption('skipinitialscan', null, InputOption::VALUE_NONE, 'Skip scanning existing keys')
            ->addOption('nooffsets', null, InputOption::VALUE_NONE, 'Disable term offsets')
            ->addOption('nofields', null, InputOption::VALUE_NONE, 'Disable field flags')
            ->addOption('nofreqs', null, InputOption::VALUE_NONE, 'Disable term frequencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('name');
        $schemaFile = $input->getArgument('schema-file');

        $index = $this->createIndex($input, $indexName);

        $index->setIndexType($input->getOption('on'));

        $prefixes = $input->getOption('prefix');
        if (!empty($prefixes)) {
            $index->setPrefixes($prefixes);
        }

        $filter = $input->getOption('filter');
        if ($filter !== null) {
            $index->setFilter($filter);
        }

        $stopwords = $input->getOption('stopwords');
        if (!empty($stopwords)) {
            $index->setStopWords($stopwords);
        }

        if ($input->getOption('maxtextfields')) {
            $index->setMaxTextFields();
        }

        $temporary = $input->getOption('temporary');
        if ($temporary !== null) {
            $index->setTemporary((int) $temporary);
        }

        if ($input->getOption('skipinitialscan')) {
            $index->setSkipInitialScan();
        }

        $index->setNoOffsetsEnabled($input->getOption('nooffsets'));
        $index->setNoFieldsEnabled($input->getOption('nofields'));
        $index->setNoFrequenciesEnabled($input->getOption('nofreqs'));

        SchemaParser::applySchema($schemaFile, $index);

        $index->create();

        $output->writeln("Index '$indexName' created successfully.");

        return self::SUCCESS;
    }
}
