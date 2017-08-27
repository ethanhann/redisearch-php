<?php

use Robo\Tasks;

require_once 'vendor/autoload.php';

class RoboFile extends Tasks
{
    function build()
    {
        $this->fixCodeStyle();
        $this->test();
    }

    function test()
    {
        return $this->taskPhpUnit()->run();
    }

    function fixCodeStyle()
    {
        return $this->_exec('./vendor/bin/php-cs-fixer fix src');
    }
}
