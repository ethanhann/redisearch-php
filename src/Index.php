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
    private string $indexType = 'HASH';
    private ?string $filter = null;
    private bool $maxTextFields = false;
    private ?int $temporary = null;
    private bool $skipInitialScan = false;

    /**
     * @return mixed
     * @throws NoFieldsInIndexException
     */
    public function create()
    {
        $properties = [$this->getIndexName()];

        $properties[] = 'ON';
        $properties[] = $this->indexType;

        if (!is_null($this->filter)) {
            $properties[] = 'FILTER';
            $properties[] = $this->filter;
        }
        if ($this->maxTextFields) {
            $properties[] = 'MAXTEXTFIELDS';
        }
        if (!is_null($this->temporary)) {
            $properties[] = 'TEMPORARY';
            $properties[] = $this->temporary;
        }
        if ($this->skipInitialScan) {
            $properties[] = 'SKIPINITIALSCAN';
        }

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
     * Loads field definitions from an existing RediSearch index by calling FT.INFO and
     * parsing the schema. This allows working with a pre-existing index without having
     * to manually re-define all fields on every instantiation.
     *
     * @return static
     */
    public function loadFields(): static
    {
        $info = $this->info();

        // Iterate in pairs to find 'attributes' key, casting to string so that
        // Predis Status objects (used for simple-string RESP2 responses) compare correctly.
        $attributes = null;
        for ($i = 0; $i < count($info) - 1; $i += 2) {
            if ((string)$info[$i] === 'attributes') {
                $attributes = $info[$i + 1];
                break;
            }
        }

        if (!is_array($attributes)) {
            return $this;
        }

        foreach ($attributes as $attr) {
            $map = $this->parseAttributeDescriptor($attr);

            $name = (string)($map['attribute'] ?? $map['identifier'] ?? '');
            if ($name === '' || str_starts_with($name, '__')) {
                continue; // skip internal fields like __score, __language
            }

            // Cast each flag to string to handle Predis Status objects
            $rawFlags = $map['flags'] ?? [];
            $flags = is_array($rawFlags) ? array_map(fn ($f) => strtoupper((string)$f), $rawFlags) : [];
            $sortable = in_array('SORTABLE', $flags, true);
            $noindex = in_array('NOINDEX', $flags, true);
            $type = strtoupper((string)($map['type'] ?? ''));

            $field = match ($type) {
                'TEXT' => (new TextField($name))
                    ->setWeight((float)($map['weight'] ?? 1.0))
                    ->setSortable($sortable)
                    ->setNoindex($noindex)
                    ->setNoStem(in_array('NOSTEM', $flags, true)),
                'NUMERIC' => (new NumericField($name))
                    ->setSortable($sortable)
                    ->setNoindex($noindex),
                'TAG' => (new TagField($name))
                    ->setSeparator((string)($map['separator'] ?? ','))
                    ->setSortable($sortable)
                    ->setNoindex($noindex),
                'GEO' => (new GeoField($name))
                    ->setNoindex($noindex),
                'VECTOR' => new VectorField(
                    $name,
                    strtoupper((string)($map['algorithm'] ?? VectorField::ALGORITHM_FLAT)),
                    strtoupper((string)($map['data_type'] ?? VectorField::TYPE_FLOAT32)),
                    (int)($map['dim'] ?? 128),
                    strtoupper((string)($map['distance_metric'] ?? VectorField::DISTANCE_COSINE)),
                ),
                default => null,
            };

            if ($field !== null) {
                $this->fields[$name] = $field;
            }
        }

        return $this;
    }

    /**
     * Converts a flat alternating [key, value, key, value, ...] attribute descriptor
     * from FT.INFO into an associative array with lowercased keys.
     */
    private function parseAttributeDescriptor(array $attr): array
    {
        $map = [];
        $i = 0;
        $count = count($attr);
        while ($i < $count - 1) {
            $map[strtolower((string)$attr[$i])] = $attr[$i + 1];
            $i += 2;
        }
        return $map;
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
     * Sets the key prefixes used in both FT.CREATE and document key construction.
     *
     * RediSearch supports multiple PREFIX alternatives (e.g. ['post:', 'blog:'])
     * so the index covers hashes under any of those prefixes. However, when writing
     * documents via add()/replace(), only the first prefix is used to construct the
     * hash key. Each prefix must include its own separator (e.g. 'post:', not 'post').
     *
     * @param array $prefixes
     * @return IndexInterface
     */
    public function setPrefixes(array $prefixes = []): IndexInterface
    {
        $this->prefixes = $prefixes;

        return $this;
    }

    /**
     * Sets the index data type. Use 'HASH' (default) or 'JSON' (requires RedisJSON module).
     *
     * @param string $type 'HASH' or 'JSON'
     * @return IndexInterface
     */
    public function setIndexType(string $type): IndexInterface
    {
        $valid = ['HASH', 'JSON'];
        if (!in_array(strtoupper($type), $valid, true)) {
            throw new \InvalidArgumentException("Invalid index type '$type'. Expected one of: " . implode(', ', $valid));
        }
        $this->indexType = strtoupper($type);
        return $this;
    }

    /**
     * Sets a filter expression applied to documents at index creation time.
     * Only documents for which the expression is true are indexed.
     *
     * @param string $expression RediSearch filter expression (e.g. '@age > 18')
     * @return IndexInterface
     */
    public function setFilter(string $expression): IndexInterface
    {
        $this->filter = $expression;
        return $this;
    }

    /**
     * Enables MAXTEXTFIELDS, allowing more than the default 32 text attributes.
     *
     * @return IndexInterface
     */
    public function setMaxTextFields(bool $enable = true): IndexInterface
    {
        $this->maxTextFields = $enable;
        return $this;
    }

    /**
     * Creates a temporary index that expires after the given number of seconds of inactivity.
     *
     * @param int $seconds TTL in seconds
     * @return IndexInterface
     */
    public function setTemporary(int $seconds): IndexInterface
    {
        $this->temporary = $seconds;
        return $this;
    }

    /**
     * When enabled, the index is created without scanning existing keys.
     * Newly added/modified keys matching the prefix will still be indexed.
     *
     * @return IndexInterface
     */
    public function setSkipInitialScan(bool $skip = true): IndexInterface
    {
        $this->skipInitialScan = $skip;
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
    public function tagFilter(string $fieldName, array $values, ?array $charactersToEscape = null): QueryBuilderInterface
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
     * Sets named parameters for parameterized queries (e.g. vector KNN search).
     * Emits PARAMS {n} key1 val1 ... in FT.SEARCH. Requires DIALECT 2+.
     *
     * @param array $params Associative array of parameter names to values.
     * @return QueryBuilderInterface
     */
    public function params(array $params): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->params($params);
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
     *
     * Uses only the first configured prefix. RediSearch's PREFIX option accepts
     * multiple alternative prefixes (e.g. PREFIX 2 post: blog:), meaning the
     * index covers hashes under either prefix. When writing a document, a single
     * concrete prefix must be chosen — the first entry is used. Prefixes should
     * include their own separator (e.g. 'post:' not 'post').
     */
    private function buildDocumentKey(string $id): string
    {
        return !is_null($this->prefixes) && count($this->prefixes) > 0
            ? $this->prefixes[0] . $id
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

    /**
     * Adds one or more fields to an existing index schema (FT.ALTER).
     * Existing documents are not re-indexed for new fields; only newly
     * added/updated documents will include the new fields.
     *
     * @param FieldInterface ...$fields
     * @return mixed
     */
    public function alter(FieldInterface ...$fields): mixed
    {
        $args = [$this->getIndexName(), 'SCHEMA', 'ADD'];
        foreach ($fields as $field) {
            $args = array_merge($args, $field->getTypeDefinition());
        }
        return $this->rawCommand('FT.ALTER', $args);
    }

    /**
     * Returns a list of all index names in the current Redis instance (FT._LIST).
     *
     * @return array
     */
    public function listIndexes(): array
    {
        return $this->rawCommand('FT._LIST', []) ?? [];
    }

    /**
     * Creates or updates a synonym group with the given terms (FT.SYNUPDATE).
     *
     * @param string $synonymGroupId
     * @param string ...$terms
     * @return mixed
     */
    public function synUpdate(string $synonymGroupId, string ...$terms): mixed
    {
        return $this->rawCommand('FT.SYNUPDATE', array_merge([$this->getIndexName(), $synonymGroupId], $terms));
    }

    /**
     * Returns all synonym mappings for the index (FT.SYNDUMP).
     *
     * @return array
     */
    public function synDump(): array
    {
        return $this->rawCommand('FT.SYNDUMP', [$this->getIndexName()]) ?? [];
    }

    /**
     * Performs spell checking on a query string (FT.SPELLCHECK).
     * Returns suggestions for misspelled terms.
     *
     * @param string $query
     * @param int $distance Maximum Levenshtein distance for suggestions (1–4)
     * @return array
     */
    public function spellCheck(string $query, int $distance = 1): array
    {
        return $this->rawCommand('FT.SPELLCHECK', [$this->getIndexName(), $query, 'DISTANCE', $distance]) ?? [];
    }

    /**
     * Adds terms to a custom dictionary used by FT.SPELLCHECK (FT.DICTADD).
     *
     * @param string $dict Dictionary name
     * @param string ...$terms
     * @return int Number of terms added
     */
    public function dictAdd(string $dict, string ...$terms): int
    {
        return (int) $this->rawCommand('FT.DICTADD', array_merge([$dict], $terms));
    }

    /**
     * Removes terms from a custom dictionary (FT.DICTDEL).
     *
     * @param string $dict Dictionary name
     * @param string ...$terms
     * @return int Number of terms removed
     */
    public function dictDelete(string $dict, string ...$terms): int
    {
        return (int) $this->rawCommand('FT.DICTDEL', array_merge([$dict], $terms));
    }

    /**
     * Returns all terms in a custom dictionary (FT.DICTDUMP).
     *
     * @param string $dict Dictionary name
     * @return array
     */
    public function dictDump(string $dict): array
    {
        return $this->rawCommand('FT.DICTDUMP', [$dict]) ?? [];
    }
}
