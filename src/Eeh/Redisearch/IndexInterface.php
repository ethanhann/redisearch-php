<?php

namespace Eeh\Redisearch;

use Eeh\Redisearch\Query\BuilderInterface;
use Redis;

interface IndexInterface extends BuilderInterface
{
    public function create();
    public function drop();
    public function info();
    public function addDocument($document, $noSave = false, $replace = false, $language = null, $payload = null);
    public function makeDocument(): DocumentInterface;
    public function getRedis(): Redis;
    public function setRedis(Redis $redis);
    public function getIndexName(): string;
    public function setIndexName(string $indexName): IndexInterface;
    public function isNoOffsetsEnabled(): bool;
    public function setNoOffsetsEnabled(bool $noOffsetsEnabled): IndexInterface;
    public function isNoFieldsEnabled(): bool;
    public function setNoFieldsEnabled(bool $noFieldsEnabled): IndexInterface;
    public function isNoScoreIdxEnabled(): bool;
    public function setNoScoreIdxEnabled(bool $noScoreIdxEnabled): IndexInterface;
}
