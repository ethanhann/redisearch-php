<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Document\Document;
use Ehann\RediSearch\Document\Builder as DocumentBuilder;
use Ehann\RediSearch\Document\BuilderInterface as DocumentBuilderInterface;
use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use Ehann\RediSearch\Fields\FieldInterface;
use Ehann\RediSearch\Fields\GeoField;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\Query\Builder as QueryBuilder;
use Ehann\RediSearch\Query\BuilderInterface as QueryBuilderInterface;
use Ehann\RediSearch\Query\SearchResult;
use Ehann\RediSearch\Redis\RedisClient;

class Index implements IndexInterface
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

    public function __construct(RedisClient $redisClient = null, string $indexName = '')
    {
        $this->redisClient = $redisClient ?? new RedisClient();
        $this->indexName = $indexName;
    }

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
                if ($field->isSortable()) {
                    $properties[] = 'SORTABLE';
                }
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
     * @param string $name
     * @param float $weight
     * @param bool $sortable
     * @return IndexInterface
     */
    public function addTextField(string $name, float $weight = 1.0, bool $sortable = false): IndexInterface
    {
        $this->$name = (new TextField($name, null, $sortable))->setWeight($weight);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $sortable
     * @return IndexInterface
     */
    public function addNumericField(string $name, bool $sortable = false): IndexInterface
    {
        $this->$name = new NumericField($name, null, $sortable);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $sortable
     * @return IndexInterface
     */
    public function addGeoField(string $name, bool $sortable = false): IndexInterface
    {
        $this->$name = new GeoField($name, null, $sortable);
        return $this;
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
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->redisClient->rawCommand('FT.DEL', [$this->getIndexName(), $id]);
    }

    /**
     * @return int
     */
    public function optimize()
    {
        return $this->redisClient->rawCommand('FT.OPTIMIZE', [$this->getIndexName()]);
    }

    /**
     * @param null $id
     * @return Document
     */
    public function makeDocument($id = null): Document
    {
        $fields = $this->getFields();
        $document = DocumentBuilder::makeFromArray($fields, $fields, $id);
        return $document;
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
    public function numericFilter(string $fieldName, $min, $max = null): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->numericFilter($fieldName, $min, $max);
    }

    /**
     * @param string $fieldName
     * @param float $longitude
     * @param float $latitude
     * @param float $radius
     * @param string $distanceUnit
     * @return QueryBuilderInterface
     */
    public function geoFilter(string $fieldName, float $longitude, float $latitude, float $radius, string $distanceUnit = 'km'): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->geoFilter($fieldName, $longitude, $latitude, $radius, $distanceUnit);
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
     * @return QueryBuilderInterface
     */
    public function noContent(): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->noContent();
    }

    /**
     * @param int $offset
     * @param int $pageSize
     * @return QueryBuilderInterface
     */
    public function limit(int $offset, int $pageSize = 10): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->limit($offset, $pageSize);
    }

    /**
     * @param int $number
     * @param array $fields
     * @return QueryBuilderInterface
     */
    public function inFields(int $number, array $fields): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->inFields($number, $fields);
    }

    /**
     * @param int $number
     * @param array $keys
     * @return QueryBuilderInterface
     */
    public function inKeys(int $number, array $keys): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->inKeys($number, $keys);
    }

    /**
     * @param int $slop
     * @return QueryBuilderInterface
     */
    public function slop(int $slop): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->slop($slop);
    }

    /**
     * @return QueryBuilderInterface
     */
    public function noStopWords(): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->noStopWords();
    }

    /**
     * @return QueryBuilderInterface
     */
    public function withPayloads(): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->withPayloads();
    }

    /**
     * @return QueryBuilderInterface
     */
    public function withScores(): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->withScores();
    }

    /**
     * @return QueryBuilderInterface
     */
    public function verbatim(): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->verbatim();
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
     * @param array $documents
     * @param bool $disableAtomicity
     */
    public function addMany(array $documents, $disableAtomicity = false)
    {
        $this->makeDocumentBuilder()->addMany($documents, $disableAtomicity);
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
