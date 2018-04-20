<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Aggregate\BuilderInterface as AggregateBuilderInterface;
use Ehann\RediSearch\Document\DocumentInterface;
use Ehann\RediSearch\Query\BuilderInterface;
use Ehann\RedisRaw\RedisRawClientInterface;

interface IndexInterface extends BuilderInterface
{
    public function create();
    public function drop();
    public function info();
    public function delete($id);
    public function makeDocument($id = null): DocumentInterface;
    public function makeAggregateBuilder(): AggregateBuilderInterface;
    public function getRedisClient(): RedisRawClientInterface;
    public function setRedisClient(RedisRawClientInterface $redisClient): IndexInterface;
    public function getIndexName(): string;
    public function setIndexName(string $indexName): IndexInterface;
    public function isNoOffsetsEnabled(): bool;
    public function setNoOffsetsEnabled(bool $noOffsetsEnabled): IndexInterface;
    public function isNoFieldsEnabled(): bool;
    public function setNoFieldsEnabled(bool $noFieldsEnabled): IndexInterface;
    public function addTextField(string $name, float $weight = 1.0, bool $sortable = false, bool $noindex = false): IndexInterface;
    public function addNumericField(string $name, bool $sortable = false, bool $noindex = false): IndexInterface;
    public function addGeoField(string $name, bool $noindex = false): IndexInterface;
    public function add($document): bool;
    public function addMany(array $documents, $disableAtomicity = false);
    public function replace($document): bool;
    public function addHash($document): bool;
    public function replaceHash($document): bool;
}
