<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProfileCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('profile')
            ->setDescription('Profile a search query (FT.PROFILE)')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name')
            ->addArgument('query', InputArgument::REQUIRED, 'Search query')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('index');
        $query = $input->getArgument('query');

        $client = $this->createClient($input);
        $result = $client->rawCommand('FT.PROFILE', [$indexName, 'SEARCH', 'QUERY', $query]);

        if ($input->getOption('json')) {
            $this->renderJson($output, $result);
            return self::SUCCESS;
        }

        $this->printProfileResult($output, $result);

        return self::SUCCESS;
    }

    private function printProfileResult(OutputInterface $output, mixed $result, int $depth = 0): void
    {
        $indent = str_repeat('  ', $depth);

        if (is_array($result)) {
            if (array_is_list($result)) {
                foreach ($result as $item) {
                    $this->printProfileResult($output, $item, $depth);
                }
            } else {
                foreach ($result as $key => $value) {
                    if (is_array($value)) {
                        $output->writeln("{$indent}<info>{$key}:</info>");
                        $this->printProfileResult($output, $value, $depth + 1);
                    } else {
                        $output->writeln("{$indent}<info>{$key}:</info> " . (string) $value);
                    }
                }
            }
        } else {
            $output->writeln($indent . (string) $result);
        }
    }
}
