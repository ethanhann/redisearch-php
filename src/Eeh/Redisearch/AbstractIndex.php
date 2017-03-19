<?php

namespace Eeh\Redisearch;

use Eeh\Redisearch\Document\Document;
use Eeh\Redisearch\Document\Builder as DocumentBuilder;
use Eeh\Redisearch\Document\BuilderInterface as DocumentBuilderInterface;
use Eeh\Redisearch\Exceptions\NoFieldsInIndexException;
use Eeh\Redisearch\Fields\FieldInterface;
use Eeh\Redisearch\Query\Builder as QueryBuilder;
use Eeh\Redisearch\Query\BuilderInterface as QueryBuilderInterface;
use Eeh\Redisearch\Query\SearchResult;
use Eeh\Redisearch\Redis\RedisClient;

abstract class AbstractIndex implements IndexInterface
{
    /** @var RedisClient */
    private $redisClient;
    /** @var string */
    private $indexName;
    /** @var bool */
    private $noOffsetsEnabled = false;
    /** @var bool */
    private $noFieldsEnabled = false;
    /** @var bool */
    private $noScoreIdxEnabled = false;

    /**
     * @return mixed
     * @throws NoFieldsInIndexException
     */
    public function create()
    {
        $properties = [$this->getIndexName()];
        if ($this->isNoOffsetsEnabled()) {
            $properties[] = 'NOOFFSETS';
        }
        if ($this->isNoFieldsEnabled()) {
            $properties[] = 'NOFIELDS';
        }
        if ($this->isNoScoreIdxEnabled()) {
            $properties[] = 'NOSCOREIDX';
        }

        $properties[] = 'SCHEMA';
        $hasAtLeastOneField = false;
        foreach (get_object_vars($this) as $field) {
            if ($field instanceof FieldInterface) {
                $properties[] = $field->getName();
                $properties[] = $field->getType();
                $hasAtLeastOneField = true;
            }
        }

        if (!$hasAtLeastOneField) {
            throw new NoFieldsInIndexException();
        }

        return $this->redisClient->rawCommand('FT.CREATE', $properties);
    }

    /**
     * @return array
     */
    protected function getFields(): array
    {
        $fields = [];
        foreach (get_object_vars($this) as $field) {
            if ($field instanceof FieldInterface) {
                $fields[$field->getName()] = $field;
            }
        }
        return $fields;
    }

    /**
     * @return mixed
     */
    public function drop()
    {
        return $this->redisClient->rawCommand('FT.DROP', [$this->getIndexName()]);
    }

    /**
     * @return mixed
     */
    public function info()
    {
        return $this->redisClient->rawCommand('FT.INFO', [$this->getIndexName()]);
    }

    /**
     * @param bool $noSave
     * @param bool $replace
     * @param null $language
     * @param null $payload
     * @return Document
     */
    public function makeDocument($noSave = false, $replace = false, $language = null, $payload = null): Document
    {
        $fields = $this->getFields();
        return DocumentBuilder::makeFromArray($fields, $fields);
    }

    /**
     * @return RedisClient
     */
    public function getRedisClient(): RedisClient
    {
        return $this->redisClient;
    }

    /**
     * @param RedisClient $redisClient
     * @return IndexInterface
     */
    public function setRedisClient(RedisClient $redisClient): IndexInterface
    {
        $this->redisClient = $redisClient;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndexName(): string
    {
        return !is_string($this->indexName) || $this->indexName === '' ? self::class : $this->indexName;
    }

    /**
     * @param string $indexName
     * @return IndexInterface
     */
    public function setIndexName(string $indexName): IndexInterface
    {
        $this->indexName = $indexName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNoOffsetsEnabled(): bool
    {
        return $this->noOffsetsEnabled;
    }

    /**
     * @param bool $noOffsetsEnabled
     * @return IndexInterface
     */
    public function setNoOffsetsEnabled(bool $noOffsetsEnabled): IndexInterface
    {
        $this->noOffsetsEnabled = $noOffsetsEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNoFieldsEnabled(): bool
    {
        return $this->noFieldsEnabled;
    }

    /**
     * @param bool $noFieldsEnabled
     * @return IndexInterface
     */
    public function setNoFieldsEnabled(bool $noFieldsEnabled): IndexInterface
    {
        $this->noFieldsEnabled = $noFieldsEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNoScoreIdxEnabled(): bool
    {
        return $this->noScoreIdxEnabled;
    }

    /**
     * @param bool $noScoreIdxEnabled
     * @return IndexInterface
     */
    public function setNoScoreIdxEnabled(bool $noScoreIdxEnabled): IndexInterface
    {
        $this->noScoreIdxEnabled = $noScoreIdxEnabled;
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    protected function makeQueryBuilder(): QueryBuilder
    {
        return (new QueryBuilder($this->redisClient, $this->getIndexName()));
    }

    /**
     * @param string $fieldName
     * @param $min
     * @param $max
     * @return QueryBuilderInterface
     */
    public function filter(string $fieldName, $min, $max): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->filter($fieldName, $min, $max);
    }

    /**
     * @param string $query
     * @param bool $documentsAsArray
     * @return SearchResult
     */
    public function search(string $query, bool $documentsAsArray = false): SearchResult
    {
        return $this->makeQueryBuilder()->search($query, $documentsAsArray);
    }

    /**
     * @return DocumentBuilder
     */
    protected function makeDocumentBuilder(): DocumentBuilder
    {
        return (new DocumentBuilder($this->redisClient, $this->getIndexName()));
    }

    /**
     * @param $document
     * @return bool
     */
    public function add($document): bool
    {
        if (is_array($document)) {
            $document = DocumentBuilder::makeFromArray($document, $this->getFields());
        }
        return $this->makeDocumentBuilder()->add($document);
    }

    /**
     * @param $document
     * @return bool
     */
    public function replace($document): bool
    {
        if (is_array($document)) {
            $document = DocumentBuilder::makeFromArray($document, $this->getFields());
        }
        return $this->makeDocumentBuilder()->replace($document);
    }

    /**
     * @param string $id
     * @return DocumentBuilderInterface
     */
    public function id(string $id): DocumentBuilderInterface
    {
        return $this->makeDocumentBuilder()->id($id);
    }

    /**
     * @param $score
     * @return DocumentBuilderInterface
     */
    public function score($score): DocumentBuilderInterface
    {
        return $this->makeDocumentBuilder()->score($score);
    }

    /**
     * @return DocumentBuilderInterface
     */
    public function noSave(): DocumentBuilderInterface
    {
        return $this->makeDocumentBuilder()->noSave();
    }

    /**
     * @param $payload
     * @return DocumentBuilderInterface
     */
    public function payload($payload): DocumentBuilderInterface
    {
        return $this->makeDocumentBuilder()->payload($payload);
    }

    /**
     * @param $language
     * @return DocumentBuilderInterface
     */
    public function language($language): DocumentBuilderInterface
    {
        return $this->makeDocumentBuilder()->language($language);
    }
}
