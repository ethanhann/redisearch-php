<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Ehann\RediSearch\Fields\NumericField;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentAddCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('document:add')
            ->setDescription('Add a document to a RediSearch index')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name')
            ->addArgument('id', InputArgument::REQUIRED, 'Document ID')
            ->addArgument('fields', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Field values (field=value ...)')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Replace if document already exists')
            ->addOption('language', null, InputOption::VALUE_REQUIRED, 'Document language')
            ->addOption('score', null, InputOption::VALUE_REQUIRED, 'Document score (0.0-1.0)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('index');
        $docId = $input->getArgument('id');
        $fieldArgs = $input->getArgument('fields');

        $index = $this->createIndex($input, $indexName);
        $index->loadFields();

        $document = $index->makeDocument($docId);

        $language = $input->getOption('language');
        if ($language !== null) {
            $document->setLanguage($language);
        }

        $score = $input->getOption('score');
        if ($score !== null) {
            $document->setScore((float) $score);
        }

        $schema = $index->getFields();

        foreach ($fieldArgs as $fieldArg) {
            $pos = strpos($fieldArg, '=');
            if ($pos === false) {
                $output->writeln("<error>Invalid field format: '$fieldArg'. Use field=value.</error>");
                return self::FAILURE;
            }

            $name = substr($fieldArg, 0, $pos);
            $value = substr($fieldArg, $pos + 1);

            if (isset($schema[$name]) && $schema[$name] instanceof NumericField) {
                $value = is_numeric($value) ? (float) $value : $value;
            }

            $document->$name = $value;
        }

        if ($input->getOption('replace')) {
            $index->replace($document);
        } else {
            $index->add($document);
        }

        $output->writeln("Document '$docId' added to index '$indexName'.");

        return self::SUCCESS;
    }
}
