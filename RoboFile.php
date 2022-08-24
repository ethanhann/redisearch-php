<?php

use Robo\Collection\CollectionBuilder;
use Robo\Result;
use Robo\Tasks;
use Robo\Collection\Collection;

require_once 'vendor/autoload.php';

class RoboFile extends Tasks
{
    public function build(): Result
    {
        return (new Collection())
            ->add($this->taskFixCodeStyle())
            ->add($this->taskPhpUnit())
            ->run();
    }

    public function test(): Result
    {
        return $this->taskPhpUnit()->run();
    }

    public function testAll(): Result
    {
        return (new Collection())
            ->add($this->taskTestPredis())
            ->add($this->taskTestPhpRedis())
            ->add($this->taskTestRedisClient())
            ->run();
    }

    public function testPredis(): Result
    {
        return $this->taskTestPredis()->run();
    }

    public function testPhpRedis(): Result
    {
        return $this->taskTestPhpRedis()->run();
    }

    public function testRedisClient(): Result
    {
        return $this->taskTestRedisClient()->run();
    }

    public function taskFixCodeStyle(): CollectionBuilder
    {
        return $this->taskExec('./vendor/bin/php-cs-fixer fix src');
    }

    public function taskTestPredis(): CollectionBuilder
    {
        $task = $this->taskPhpUnit();
        $task->env('REDIS_LIBRARY', 'Predis');
        return $task;
    }

    public function taskTestPhpRedis(): CollectionBuilder
    {
        $task = $this->taskPhpUnit();
        $task->env('REDIS_LIBRARY', 'PhpRedis');
        return $task;
    }

    public function taskTestRedisClient(): CollectionBuilder
    {
        $task = $this->taskPhpUnit();
        $task->env('REDIS_LIBRARY', 'RedisClient');
        return $task;
    }
}
