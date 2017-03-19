<?php

namespace Eeh\Redisearch\Document;

use Eeh\Redisearch\Exceptions\FieldNotInSchemaException;
use Eeh\Redisearch\Fields\FieldFactory;
use Eeh\Redisearch\Fields\FieldInterface;
use Eeh\Redisearch\Redis\RedisClient;

class Builder implements BuilderInterface
{
    protected $id;
    protected $score = 1.0;
    protected $noSave = false;
    protected $replace = false;
    protected $payload;
    protected $language;
    /** @var Redis */
    private $redis;
    /** @var string */
    private $indexName;

    public function __construct(RedisClient $redis, string $indexName)
    {
        $this->redis = $redis;
        $this->indexName = $indexName;
    }

    public static function makeFromArray(array $fields, array $availableSchemaFields, $id = null): Document
    {
        $document = new Document($id);
        foreach ($fields as $index => $field) {
            if (is_string($index)) {
                if (!isset($availableSchemaFields[$index])) {
                    throw new FieldNotInSchemaException($index);
                }
                $document->{$index} = ($field instanceof FieldInterface) ?
                    $availableSchemaFields[$index]->setValue($field) :
                    FieldFactory::make($index, $field);
            } elseif ($field instanceof FieldInterface) {
                if (!in_array($field->getName(), array_keys($availableSchemaFields))) {
                    throw new FieldNotInSchemaException($field->getName());
                }
                $document->{$field->getName()} = $field;
            }
        }
        return $document;
    }

    public function add($document): bool
    {
        $properties = [
            $this->indexName,
            $this->id ?? uniqid(true),
            $this->score,
        ];

        if ($this->noSave) {
            $properties[] = 'NOSAVE';
        }

        if ($this->replace) {
            $properties[] = 'REPLACE';
        }

        if (!is_null($this->language)) {
            $properties[] = 'LANGUAGE';
            $properties[] = $this->language;
        }

        if (!is_null($this->payload)) {
            $properties[] = 'PAYLOAD';
            $properties[] = $this->payload;
        }

        $properties[] = 'FIELDS';

        /** @var FieldInterface $field */
        foreach (get_object_vars($document) as $field) {
            if ($field instanceof FieldInterface) {
                $properties[] = $field->getName();
                $properties[] = $field->getValue();
            }
        }

        return $this->redis->rawCommand('FT.ADD', $properties);
    }

    public function replace($document): bool
    {
        $this->replace = true;
        return $this->add($document);
    }

    public function id(string $id): BuilderInterface
    {
        $this->id = $id;
        return $this;
    }

    public function score($score): BuilderInterface
    {
        $this->score = $score;
        return $this;
    }

    public function noSave(): BuilderInterface
    {
        $this->noSave = true;
        return $this;
    }

    public function payload($payload): BuilderInterface
    {
        $this->payload = $payload;
        return $this;
    }

    public function language($language): BuilderInterface
    {
        $this->language = $language;
        return $this;
    }
}
