<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Document\AbstractDocumentFactory;
use Ehann\RediSearch\Document\DocumentInterface;
use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use Ehann\RediSearch\Fields\FieldInterface;
use Ehann\RediSearch\Fields\GeoField;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\Query\Builder as QueryBuilder;
use Ehann\RediSearch\Query\BuilderInterface as QueryBuilderInterface;
use Ehann\RediSearch\Query\SearchResult;
use Ehann\RediSearch\Redis\RedisClient;

class Index extends AbstractIndex implements IndexInterface
{
    /** @var bool */
    private $noOffsetsEnabled = false;
    /** @var bool */
    private $noFieldsEnabled = false;
    /** @var array */
    private $stopWords = null;

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
        if (!is_null($this->stopWords)) {
            $properties[] = 'STOPWORDS';
            $properties[] = count($this->stopWords);
            $properties = array_merge($properties, $this->stopWords);
        }
        $properties[] = 'SCHEMA';

        $fieldDefinitions = [];
        foreach (get_object_vars($this) as $field) {
            if ($field instanceof FieldInterface) {
                $fieldDefinitions = array_merge($fieldDefinitions, $field->getTypeDefinition());
            }
        }

        if (count($fieldDefinitions) === 0) {
            throw new NoFieldsInIndexException();
        }

        return $this->rawCommand('FT.CREATE', array_merge($properties, $fieldDefinitions));
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
        $this->$name = (new TextField($name))->setSortable($sortable)->setWeight($weight);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $sortable
     * @return IndexInterface
     */
    public function addNumericField(string $name, bool $sortable = false): IndexInterface
    {
        $this->$name = (new NumericField($name))->setSortable($sortable);
        return $this;
    }

    /**
     * @param string $name
     * @return IndexInterface
     */
    public function addGeoField(string $name): IndexInterface
    {
        $this->$name = (new GeoField($name));
        return $this;
    }

    /**
     * @return mixed
     */
    public function drop()
    {
        return $this->rawCommand('FT.DROP', [$this->getIndexName()]);
    }

    /**
     * @return mixed
     */
    public function info()
    {
        return $this->rawCommand('FT.INFO', [$this->getIndexName()]);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        return boolval($this->rawCommand('FT.DEL', [$this->getIndexName(), $id]));
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return mixed
     */
    protected function rawCommand(string $command, array $arguments)
    {
        return $this->redisClient->rawCommand($command, $arguments);
    }

    /**
     * @param null $id
     * @return DocumentInterface
     */
    public function makeDocument($id = null): DocumentInterface
    {
        $fields = $this->getFields();
        $document = AbstractDocumentFactory::makeFromArray($fields, $fields, $id);
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
     * @param string $fieldName
     * @param $order
     * @return QueryBuilderInterface
     */
    public function sortBy(string $fieldName, $order = 'ASC'): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->sortBy($fieldName, $order);
    }

    /**
     * @param string $query
     * @return string
     */
    public function explain(string $query): string
    {
        return $this->makeQueryBuilder()->explain($query);
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
     * @param array $documents
     * @param bool $disableAtomicity
     */
    public function addMany(array $documents, $disableAtomicity = false)
    {
        $pipe = $this->redisClient->multi($disableAtomicity);
        foreach ($documents as $document) {
            $this->_add($document);
        }
        $pipe->exec();
    }

    /**
     * @param DocumentInterface $document
     * @return bool
     */
    protected function _add(DocumentInterface $document)
    {
        if (is_null($document->getId())) {
            $document->setId(uniqid(true));
        }

        $properties = $document->getDefinition();
        array_unshift($properties, $this->indexName);

        return $this->rawCommand('FT.ADD', $properties);
    }

    /**
     * @param $document
     * @return bool
     */
    public function add($document): bool
    {
        if (is_array($document)) {
            $document = AbstractDocumentFactory::makeFromArray($document, $this->getFields());
        }
        return $this->_add($document);
    }

    /**
     * @param $document
     * @return bool
     */
    public function replace($document): bool
    {
        if (is_array($document)) {
            $document = AbstractDocumentFactory::makeFromArray($document, $this->getFields());
        }
        $document->setReplace(true);
        return $this->add($document);
    }
}
