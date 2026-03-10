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

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('redisearch', '1.0.0');

        $this->addCommand(new IndexCreateCommand());
        $this->addCommand(new IndexDropCommand());
        $this->addCommand(new IndexListCommand());
        $this->addCommand(new IndexInfoCommand());
        $this->addCommand(new DocumentAddCommand());
        $this->addCommand(new DocumentGetCommand());
        $this->addCommand(new DocumentDeleteCommand());
        $this->addCommand(new SearchCommand());
        $this->addCommand(new AggregateCommand());
        $this->addCommand(new ExplainCommand());
        $this->addCommand(new ProfileCommand());
        $this->addCommand(new ShellCommand());
    }
}
