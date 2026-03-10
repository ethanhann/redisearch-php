<?php

namespace Ehann\RediSearch\Console;

use Ehann\RediSearch\Console\Command\AggregateCommand;
use Ehann\RediSearch\Console\Command\DocumentAddCommand;
use Ehann\RediSearch\Console\Command\DocumentDeleteCommand;
use Ehann\RediSearch\Console\Command\DocumentGetCommand;
use Ehann\RediSearch\Console\Command\ExplainCommand;
use Ehann\RediSearch\Console\Command\IndexCreateCommand;
use Ehann\RediSearch\Console\Command\IndexDropCommand;
use Ehann\RediSearch\Console\Command\IndexInfoCommand;
use Ehann\RediSearch\Console\Command\IndexListCommand;
use Ehann\RediSearch\Console\Command\ProfileCommand;
use Ehann\RediSearch\Console\Command\SearchCommand;
use Ehann\RediSearch\Console\Command\ShellCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('redisearch', '1.0.0');

        $this->registerCommands(
            new IndexCreateCommand(),
            new IndexDropCommand(),
            new IndexListCommand(),
            new IndexInfoCommand(),
            new DocumentAddCommand(),
            new DocumentGetCommand(),
            new DocumentDeleteCommand(),
            new SearchCommand(),
            new AggregateCommand(),
            new ExplainCommand(),
            new ProfileCommand(),
            new ShellCommand(),
        );
    }

    private function registerCommands(Command ...$commands): void
    {
        foreach ($commands as $command) {
            if (method_exists($this, 'addCommand')) {
                $this->addCommand($command); // Symfony 8+
            } else {
                $this->add($command); // Symfony <=7
            }
        }
    }
}