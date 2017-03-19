<?php

namespace Eeh\Redisearch;

use Eeh\Redisearch\Exceptions\NoFieldsInIndexException;
use Eeh\Redisearch\Fields\FieldInterface;
use Eeh\Redisearch\Query\Builder;
use Eeh\Redisearch\Query\BuilderInterface;
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
     *
     */
    public function drop()
    {
        $this->callCommand(['FT.DROP', $this->getIndexName()]);
    }

    /**
     * @return mixed
     */
    public function info()
    {
        return $this->callCommand(['FT.INFO', $this->getIndexName()]);
    }

    /**
     * @param DocumentInterface|array $document
     * @param bool $noSave
     * @param bool $replace
     * @param null $language
     * @param null $payload
     * @return mixed
     */
    public function addDocument($document, $noSave = false, $replace = false, $language = null, $payload = null)
    {
        if (is_array($document)) {
            $document = Document::makeFromArray($document, $this->getFields(), $noSave, $replace, $language, $payload);
        }
        return $this->indexDocument($document);
    }

    /**
     * @param DocumentInterface $document
     * @return mixed
     */
    protected function indexDocument(DocumentInterface $document)
    {
        return $this->callCommand(array_merge(['FT.ADD', $this->getIndexName()], $document->getDefinition()));
    }



    /**
     * @param bool $noSave
     * @param bool $replace
     * @param null $language
     * @param null $payload
     * @return DocumentInterface
     */
    public function makeDocument($noSave = false, $replace = false, $language = null, $payload = null): DocumentInterface
    {
        $fields = $this->getFields();
        return Document::makeFromArray($fields, $fields, $noSave, $replace, $language, $payload);
    }

    /**
     * @param array $args
     * @return mixed
     */
    protected function callCommand(array $args)
    {
//        print PHP_EOL . implode(' ', $args);
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

    protected function makeBuilder()
    {
        return (new Builder($this->redis, $this->getIndexName()));
    }

    public function filter(string $fieldName, $min, $max): BuilderInterface
    {
        return $this->makeBuilder()->filter($fieldName, $min, $max);
    }

    public function search(string $query, bool $documentsAsArray = false): SearchResult
    {
        return $this->makeBuilder()->search($query, $documentsAsArray);
    }
}
