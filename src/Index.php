<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Aggregate\Builder as AggregateBuilder;
use Ehann\RediSearch\Aggregate\BuilderInterface as AggregateBuilderInterface;
use Ehann\RediSearch\Document\AbstractDocumentFactory;
use Ehann\RediSearch\Document\DocumentInterface;
use Ehann\RediSearch\Exceptions\DocumentAlreadyInIndexException;
use Ehann\RediSearch\Exceptions\NoFieldsInIndexException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Exceptions\UnsupportedRediSearchLanguageException;
use Ehann\RediSearch\Fields\FieldInterface;
use Ehann\RediSearch\Fields\GeoField;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TagField;
use Ehann\RediSearch\Fields\TextField;
use Ehann\RediSearch\Fields\VectorField;
use Ehann\RediSearch\Query\Builder as QueryBuilder;
use Ehann\RediSearch\Query\BuilderInterface as QueryBuilderInterface;
use Ehann\RediSearch\Query\SearchResult;
use Ehann\RedisRaw\Exceptions\RawCommandErrorException;
use RedisException;

class Index extends AbstractIndex implements IndexInterface
{
    /** @var bool */
    private $noOffsetsEnabled = false;
    /** @var bool */
    private $noFieldsEnabled = false;
    /** @var bool */
    private $noFrequenciesEnabled = false;
    /** @var array */
    private $stopWords = null;
    /** @var array|null */
    private $prefixes;
    /** @var array */
    private $fields = [];

    /**
     * @return mixed
     * @throws NoFieldsInIndexException
     */
    public function create()
    {
        $properties = [$this->getIndexName()];

        if (!is_null($this->prefixes)) {
            $properties[] = 'PREFIX';
            $properties[] = count($this->prefixes);
            $properties = array_merge($properties, $this->prefixes);
        }
        if ($this->isNoOffsetsEnabled()) {
            $properties[] = 'NOOFFSETS';
        }
        if ($this->isNoFieldsEnabled()) {
            $properties[] = 'NOFIELDS';
        }
        if ($this->isNoFrequenciesEnabled()) {
            $properties[] = 'NOFREQS';
        }
        if (!is_null($this->stopWords)) {
            $properties[] = 'STOPWORDS';
            $properties[] = count($this->stopWords);
            $properties = array_merge($properties, $this->stopWords);
        }
        $properties[] = 'SCORE_FIELD';
        $properties[] = '__score';
        $properties[] = 'LANGUAGE_FIELD';
        $properties[] = '__language';
        $properties[] = 'SCHEMA';

        $fieldDefinitions = [];
        foreach ($this->getFields() as $field) {
            $fieldDefinitions = array_merge($fieldDefinitions, $field->getTypeDefinition());
        }

        if (count($fieldDefinitions) === 0) {
            throw new NoFieldsInIndexException();
        }

        return $this->rawCommand('FT.CREATE', array_merge($properties, $fieldDefinitions));
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        try {
            $this->info();
            return true;
        } catch (UnknownIndexNameException $exception) {
            return false;
        }
    }

    /**
     * @param string $name
     * @param FieldInterface $value
     *
     * @return void
     */
    public function __set(string $name, FieldInterface $value): void
    {
        $this->fields[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->fields) !== false;
    }

    /**
     * @param string $name
     *
     * @return ?FieldInterface
     */
    public function __get(string $name): ?FieldInterface
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns an array of fields as cloned objects
     *
     * @return array
     */
    public function getFieldsCloned(): array
    {
        return array_map(fn ($field) => clone $field, $this->fields);
    }

    /**
     * @param string $name
     * @param float $weight
     * @param bool $sortable
     * @param bool $noindex
     * @return IndexInterface
     */
    public function addTextField(string $name, float $weight = 1.0, bool $sortable = false, bool $noindex = false): IndexInterface
    {
        $this->$name = (new TextField($name))->setSortable($sortable)->setNoindex($noindex)->setWeight($weight);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $sortable
     * @param bool $noindex
     * @return IndexInterface
     */
    public function addNumericField(string $name, bool $sortable = false, bool $noindex = false): IndexInterface
    {
        $this->$name = (new NumericField($name))->setSortable($sortable)->setNoindex($noindex);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $noindex
     * @return IndexInterface
     */
    public function addGeoField(string $name, bool $noindex = false): IndexInterface
    {
        $this->$name = (new GeoField($name))->setNoindex($noindex);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $sortable
     * @param bool $noindex
     * @param string $separator
     * @return IndexInterface
     */
    public function addTagField(string $name, bool $sortable = false, bool $noindex = false, string $separator = ','): IndexInterface
    {
        $this->$name = (new TagField($name))->setSortable($sortable)->setNoindex($noindex)->setSeparator($separator);
        return $this;
    }

    /**
     * Adds a VECTOR field to the index schema. Available in RediSearch v2.2+.
     *
     * @param string $name
     * @param string $algorithm FLAT or HNSW
     * @param string $type FLOAT32 or FLOAT64
     * @param int $dim Number of vector dimensions
     * @param string $distanceMetric L2, IP, or COSINE
     * @param array $extraAttributes Additional algorithm-specific attributes (key => value pairs)
     * @return IndexInterface
     */
    public function addVectorField(
        string $name,
        string $algorithm = VectorField::ALGORITHM_FLAT,
        string $type = VectorField::TYPE_FLOAT32,
        int $dim = 128,
        string $distanceMetric = VectorField::DISTANCE_COSINE,
        array $extraAttributes = []
    ): IndexInterface {
        $this->$name = new VectorField($name, $algorithm, $type, $dim, $distanceMetric, $extraAttributes);
        return $this;
    }

    /**
     * @param string $name
     * @return array
     */
    public function tagValues(string $name): array
    {
        return $this->rawCommand('FT.TAGVALS', [$this->getIndexName(), $name]);
    }

    /**
     * @param bool $deleteDocuments When true, also deletes all documents (hashes) associated with this index.
     * @return mixed
     */
    public function drop(bool $deleteDocuments = false)
    {
        $arguments = [$this->getIndexName()];
        if ($deleteDocuments) {
            $arguments[] = 'DD';
        }
        return $this->rawCommand('FT.DROPINDEX', $arguments);
    }

    /**
     * @return mixed
     */
    public function info()
    {
        return $this->rawCommand('FT.INFO', [$this->getIndexName()]);
    }

    /**
     * Deletes a document by its ID. In RediSearch v2.x documents are stored as Redis hashes,
     * so this deletes the underlying hash key, removing the document from the index.
     *
     * @param string $id The document ID.
     * @param bool $deleteDocument Kept for API compatibility; deletion always removes the hash in v2.x.
     * @return bool
     */
    public function delete($id, $deleteDocument = false)
    {
        $key = $this->buildDocumentKey($id);
        return boolval($this->rawCommand('DEL', [$key]));
    }

    /**
     * @param null $id
     * @return DocumentInterface
     * @throws Exceptions\FieldNotInSchemaException
     */
    public function makeDocument($id = null): DocumentInterface
    {
        $fields = $this->getFieldsCloned();
        $document = AbstractDocumentFactory::makeFromArray($fields, $fields, $id);
        return $document;
    }

    /**
     * @return AggregateBuilderInterface
     */
    public function makeAggregateBuilder(): AggregateBuilderInterface
    {
        return new AggregateBuilder($this->getRedisClient(), $this->getIndexName());
    }

    /**
     * @return RediSearchRedisClient
     */
    public function getRedisClient(): RediSearchRedisClient
    {
        return $this->redisClient;
    }

    /**
     * @param RediSearchRedisClient $redisClient
     * @return IndexInterface
     */
    public function setRedisClient(RediSearchRedisClient $redisClient): IndexInterface
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
    public function isNoFrequenciesEnabled(): bool
    {
        return $this->noFrequenciesEnabled;
    }

    /**
     * @param bool $noFrequenciesEnabled
     * @return IndexInterface
     */
    public function setNoFrequenciesEnabled(bool $noFrequenciesEnabled): IndexInterface
    {
        $this->noFrequenciesEnabled = $noFrequenciesEnabled;
        return $this;
    }

    /**
     * @param array $stopWords
     * @return IndexInterface
     */
    public function setStopWords(array $stopWords = []): IndexInterface
    {
        $this->stopWords = $stopWords;
        return $this;
    }

    /**
     * @param array $prefixes
     *
     * @return IndexInterface
     */
    public function setPrefixes(array $prefixes = []): IndexInterface
    {
        $this->prefixes = $prefixes;

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
     * @param array $values
     * @param array|null $charactersToEscape
     * @return QueryBuilderInterface
     */
    public function tagFilter(string $fieldName, array $values, array $charactersToEscape = null): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->tagFilter($fieldName, $values, $charactersToEscape);
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
     * @param string $scoringFunction
     * @return QueryBuilderInterface
     */
    public function scorer(string $scoringFunction): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->scorer($scoringFunction);
    }

    /**
     * @param string $languageName
     * @return QueryBuilderInterface
     */
    public function language(string $languageName): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->language($languageName);
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
     * Sets the query dialect. Available in RediSearch v2.4+.
     *
     * @param int $version Dialect version (1, 2, or 3)
     * @return QueryBuilderInterface
     */
    public function dialect(int $version): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->dialect($version);
    }

    /**
     * @param string $query
     * @param bool $documentsAsArray
     * @return SearchResult
     * @throws \Ehann\RedisRaw\Exceptions\RedisRawCommandException
     */
    public function search(string $query = '', bool $documentsAsArray = false): SearchResult
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
     * Builds the Redis key for a document, incorporating any configured prefix.
     */
    private function buildDocumentKey(string $id): string
    {
        return !is_null($this->prefixes) && count($this->prefixes) > 0
            ? implode(':', $this->prefixes) . ':' . $id
            : $id;
    }

    /**
     * Core HSET operation — stores a document as a Redis hash. No existence checks.
     * Used internally by addMany() and addHash().
     *
     * @param DocumentInterface $document
     * @return mixed
     */
    protected function _add(DocumentInterface $document)
    {
        if (is_null($document->getId())) {
            $document->setId(uniqid(true));
        }

        $properties = $document->getHashDefinition($this->prefixes);
        return $this->rawCommand('HSET', $properties);
    }

    /**
     * @param array $documents
     * @param bool $disableAtomicity
     * @param bool $replace Kept for API compatibility; HSET always upserts.
     */
    public function addMany(array $documents, $disableAtomicity = false, $replace = false)
    {
        $result = null;

        $pipe = $this->redisClient->multi($disableAtomicity);
        foreach ($documents as $document) {
            if (is_array($document)) {
                $document = $this->arrayToDocument($document);
            }
            $this->_add($document);
        }
        try {
            $pipe->exec();
        } catch (RedisException $exception) {
            $result = $exception->getMessage();
        } catch (RawCommandErrorException $exception) {
            $result = $exception->getPrevious()->getMessage();
        }

        if ($result) {
            $this->redisClient->validateRawCommandResults($result, 'PIPE', [$this->indexName, '*MANY']);
        }
    }

    /**
     * @param $document
     * @return DocumentInterface
     * @throws Exceptions\FieldNotInSchemaException
     */
    public function arrayToDocument($document): DocumentInterface
    {
        return is_array($document) ? AbstractDocumentFactory::makeFromArray($document, $this->getFields()) : $document;
    }

    /**
     * Adds a new document to the index. Throws if the index does not exist or the
     * document ID already exists in Redis.
     *
     * @param $document
     * @return bool
     * @throws Exceptions\FieldNotInSchemaException
     * @throws DocumentAlreadyInIndexException
     * @throws UnsupportedRediSearchLanguageException
     */
    public function add($document): bool
    {
        $typedDocument = $this->arrayToDocument($document);

        // Ensure the index exists — throws UnknownIndexNameException if not.
        $this->info();

        // Validate language before storing.
        if (!is_null($typedDocument->getLanguage()) && !Language::isSupported($typedDocument->getLanguage())) {
            throw new UnsupportedRediSearchLanguageException();
        }

        if (is_null($typedDocument->getId())) {
            $typedDocument->setId(uniqid(true));
        }

        $key = $this->buildDocumentKey($typedDocument->getId());
        if ($this->rawCommand('EXISTS', [$key])) {
            throw new DocumentAlreadyInIndexException($this->getIndexName(), $typedDocument->getId());
        }

        return boolval($this->_add($typedDocument));
    }

    /**
     * Updates (upserts) a document in the index using HSET.
     *
     * @param $document
     * @return bool
     * @throws Exceptions\FieldNotInSchemaException
     */
    public function replace($document): bool
    {
        $this->_add($this->arrayToDocument($document));
        return true;
    }

    /**
     * @param array $documents
     * @param bool $disableAtomicity
     */
    public function replaceMany(array $documents, $disableAtomicity = false)
    {
        $this->addMany($documents, $disableAtomicity, true);
    }

    /**
     * Adds or replaces a document stored as a Redis hash. Upsert semantics (HSET).
     *
     * @param $document
     * @return bool
     * @throws Exceptions\FieldNotInSchemaException
     */
    public function addHash($document): bool
    {
        $this->_add($this->arrayToDocument($document));
        return true;
    }

    /**
     * Replaces a document stored as a Redis hash. Alias for addHash() — HSET always upserts.
     *
     * @param $document
     * @return bool
     * @throws Exceptions\FieldNotInSchemaException
     */
    public function replaceHash($document): bool
    {
        $this->_add($this->arrayToDocument($document));
        return true;
    }

    /**
     * @param array $fields
     * @return QueryBuilderInterface
     */
    public function return(array $fields): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->return($fields);
    }

    /**
     * @param array $fields
     * @param int $fragmentCount
     * @param int $fragmentLength
     * @param string $separator
     * @return QueryBuilderInterface
     */
    public function summarize(array $fields, int $fragmentCount = 3, int $fragmentLength = 50, string $separator = '...'): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->summarize($fields, $fragmentCount, $fragmentLength, $separator);
    }

    /**
     * @param array $fields
     * @param string $openTag
     * @param string $closeTag
     * @return QueryBuilderInterface
     */
    public function highlight(array $fields, string $openTag = '<strong>', string $closeTag = '</strong>'): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->highlight($fields, $openTag, $closeTag);
    }

    /**
     * @param string $expander
     * @return QueryBuilderInterface
     */
    public function expander(string $expander): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->expander($expander);
    }

    /**
     * @param string $payload
     * @return QueryBuilderInterface
     */
    public function payload(string $payload): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->payload($payload);
    }

    /**
     * @param string $query
     * @return int
     */
    public function count(string $query = ''): int
    {
        return $this->makeQueryBuilder()->count($query);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function addAlias(string $name): bool
    {
        return $this->rawCommand('FT.ALIASADD', [$name, $this->getIndexName()]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function updateAlias(string $name): bool
    {
        return $this->rawCommand('FT.ALIASUPDATE', [$name, $this->getIndexName()]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function deleteAlias(string $name): bool
    {
        return $this->rawCommand('FT.ALIASDEL', [$name]);
    }
}
