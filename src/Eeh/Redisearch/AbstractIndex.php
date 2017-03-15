<?php

namespace Eeh\Redisearch;

use Eeh\Redisearch\Exceptions\NoFieldsInIndexException;
use Eeh\Redisearch\Fields\FieldInterface;
use Redis;

abstract class AbstractIndex implements IndexInterface
{
    /** @var Redis */
    private $redis;
    /** @var string */
    private $indexName;
    private $noOffsetsEnabled = false;
    private $noFieldsEnabled = false;
    private $noScoreIdxEnabled = false;

    /**
     * @return mixed
     * @throws NoFieldsInIndexException
     */
    public function create()
    {
        $properties = ['FT.CREATE', $this->getIndexName()];
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

        return $this->callCommand($properties);
    }

    /**
     *
     */
    public function drop()
    {
        $this->redis->rawCommand('FT.DROP', $this->getIndexName());
    }

    /**
     * @return mixed
     */
    public function info()
    {
        return $this->redis->rawCommand('FT.INFO', $this->getIndexName());
    }

    /**
     * @param array $fields
     * @param bool $noSave
     * @param bool $replace
     * @param null $language
     * @param null $payload
     * @return mixed
     */
    public function addDocument(array $fields, $noSave = false, $replace = false, $language = null, $payload = null)
    {
        $document = Document::makeFromArray($fields)
            ->setNoSave($noSave)
            ->setReplace($replace)
            ->setLanguage($language)
            ->setPayload($payload);
        return $this->indexDocument($document);
    }

    /**
     * @param DocumentInterface $document
     * @return mixed
     */
    public function indexDocument(DocumentInterface $document)
    {
        return $this->callCommand(array_merge(['FT.ADD', $this->getIndexName()], $document->getDefinition()));
    }

    /**
     * @param $query
     * @param bool $documentsAsArray
     * @return SearchResult
     */
    public function search($query, bool $documentsAsArray = false): SearchResult
    {
        return SearchResult::makeSearchResult(
            $this->callCommand(['FT.SEARCH', $this->getIndexName(), $query]),
            $documentsAsArray
        );
    }

    /**
     * @param array $args
     * @return mixed
     */
    protected function callCommand(array $args)
    {
        return call_user_func_array([$this->redis, 'rawCommand'], $args);
    }

    /**
     * @return Redis
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * @param Redis $redis
     * @return IndexInterface
     */
    public function setRedis(Redis $redis): IndexInterface
    {
        $this->redis = $redis;
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
}
