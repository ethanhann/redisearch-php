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

    function testAll()
    {
        return (new Collection())
            ->add($this->taskTestPredis())
            ->add($this->taskTestPhpRedis())
            ->add($this->taskTestRedisClient())
            ->run();
    }

    function testPredis()
    {
        return $this->taskTestPredis()->run();
    }

    function testPhpRedis()
    {
        return $this->taskTestPhpRedis()->run();
    }

    function testRedisClient()
    {
        return $this->taskTestRedisClient()->run();
    }

    function taskFixCodeStyle()
    {
        return $this->taskExec('./vendor/bin/php-cs-fixer fix src');
    }

    function taskTestPredis()
    {
        $task = $this->taskPhpUnit();
        $task->env('REDIS_LIBRARY', 'Predis');
        return $task;
    }

    function taskTestPhpRedis()
    {
        $task = $this->taskPhpUnit();
        $task->env('REDIS_LIBRARY', 'PhpRedis');
        return $task;
    }

    function taskTestRedisClient()
    {
        $task = $this->taskPhpUnit();
        $task->env('REDIS_LIBRARY', 'RedisClient');
        return $task;
    }
}
