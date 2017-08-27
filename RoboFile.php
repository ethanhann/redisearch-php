<?php

use Robo\Tasks;
use Robo\Collection\Collection;

require_once 'vendor/autoload.php';

class RoboFile extends Tasks
{
    function build()
    {
        return (new Collection())
            ->add($this->taskFixCodeStyle())
            ->add($this->taskPhpUnit())
            ->run();
    }

    function test()
    {
        return $this->taskPhpUnit()->run();
    }

    function taskFixCodeStyle()
    {
        return $this->taskExec('./vendor/bin/php-cs-fixer fix src');
    }

    function fixCodeStyle()
    {
        return $this->taskFixCodeStyle()->run();
    }
}
