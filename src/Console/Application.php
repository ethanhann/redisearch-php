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

        $this->add(new IndexCreateCommand());
        $this->add(new IndexDropCommand());
        $this->add(new IndexListCommand());
        $this->add(new IndexInfoCommand());
        $this->add(new DocumentAddCommand());
        $this->add(new DocumentGetCommand());
        $this->add(new DocumentDeleteCommand());
        $this->add(new SearchCommand());
        $this->add(new AggregateCommand());
        $this->add(new ExplainCommand());
        $this->add(new ProfileCommand());
        $this->add(new ShellCommand());
    }
}
