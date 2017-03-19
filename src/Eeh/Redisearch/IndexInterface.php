<?php

namespace Eeh\Redisearch;

use Eeh\Redisearch\Document\BuilderInterface as DocumentBuilderInterface;
use Eeh\Redisearch\Document\Document;
use Eeh\Redisearch\Query\BuilderInterface as QueryBuilderInterface;
use Eeh\Redisearch\Redis\RedisClient;

interface IndexInterface extends DocumentBuilderInterface, QueryBuilderInterface
{
    public function create();
    public function drop();
    public function info();
    public function makeDocument(): Document;
    public function getRedisClient(): RedisClient;
    public function setRedisClient(RedisClient $redisClient);
    public function getIndexName(): string;
    public function setIndexName(string $indexName): IndexInterface;
    public function isNoOffsetsEnabled(): bool;
    public function setNoOffsetsEnabled(bool $noOffsetsEnabled): IndexInterface;
    public function isNoFieldsEnabled(): bool;
    public function setNoFieldsEnabled(bool $noFieldsEnabled): IndexInterface;
    public function isNoScoreIdxEnabled(): bool;
    public function setNoScoreIdxEnabled(bool $noScoreIdxEnabled): IndexInterface;
}
