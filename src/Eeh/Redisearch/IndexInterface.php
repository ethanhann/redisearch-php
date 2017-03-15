<?php

namespace Eeh\Redisearch;

use Redis;

interface IndexInterface
{
    public function create();
    public function drop();
    public function info();
    public function addDocument(array $fields, $noSave = false, $replace = false, $language = null, $payload = null);
    public function indexDocument(DocumentInterface $document);
    public function search($query, bool $documentsAsArray = false) : SearchResult;
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
