<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Aggregate\BuilderInterface as AggregateBuilderInterface;
use Ehann\RediSearch\Document\DocumentInterface;
use Ehann\RediSearch\Query\BuilderInterface;

interface IndexInterface extends BuilderInterface
{
    public function create();
    public function exists(): bool;
    public function drop();
    public function info();
    public function delete($id, $deleteDocument = false);
    public function getFields(): array;
    public function makeDocument($id = null): DocumentInterface;
    public function makeAggregateBuilder(): AggregateBuilderInterface;
    public function getRedisClient(): RediSearchRedisClient;
    public function setRedisClient(RediSearchRedisClient $redisClient): IndexInterface;
    public function getIndexName(): string;
    public function setIndexName(string $indexName): IndexInterface;
    public function isNoOffsetsEnabled(): bool;
    public function setNoOffsetsEnabled(bool $noOffsetsEnabled): IndexInterface;
    public function isNoFieldsEnabled(): bool;
    public function setNoFieldsEnabled(bool $noFieldsEnabled): IndexInterface;
    public function isNoFrequenciesEnabled(): bool;
    public function setNoFrequenciesEnabled(bool $noFieldsEnabled): IndexInterface;
    public function setStopWords(array $stopWords): IndexInterface;
    public function setPrefixes(array $prefixes): IndexInterface;
    public function addTextField(string $name, float $weight = 1.0, bool $sortable = false, bool $noindex = false): IndexInterface;
    public function addNumericField(string $name, bool $sortable = false, bool $noindex = false): IndexInterface;
    public function addGeoField(string $name, bool $noindex = false): IndexInterface;
    public function addTagField(string $name, bool $sortable = false, bool $noindex = false, string $separator = ','): IndexInterface;
    public function tagValues(string $name): array;
    public function add($document): bool;
    public function addMany(array $documents, $disableAtomicity = false, $replace = false);
    public function replace($document): bool;
    public function replaceMany(array $documents, $disableAtomicity = false);
    public function addHash($document): bool;
    public function replaceHash($document): bool;
    public function addAlias(string $name): bool;
    public function updateAlias(string $name): bool;
    public function deleteAlias(string $name): bool;
}
