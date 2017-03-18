<?php

namespace Eeh\Redisearch;

use Eeh\Redisearch\Exceptions\FieldNotInSchemaException;
use Eeh\Redisearch\Fields\FieldFactory;
use Eeh\Redisearch\Fields\FieldInterface;

class Document implements DocumentInterface
{
    protected $id;
    protected $score = 1.0;
    protected $noSave = false;
    protected $replace = false;
    protected $payload;
    protected $language;

    public function __construct($id = null)
    {
        $this->id = $id ?? uniqid(true);
    }

    public static function makeFromArray(
        array $fields,
        array $availableSchemaFields,
        $noSave = false,
        $replace = false,
        $language = null,
        $payload = null
    ): Document {
        $document = new Document();
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
        return $document
            ->setNoSave($noSave)
            ->setReplace($replace)
            ->setLanguage($language)
            ->setPayload($payload);
    }

    public function getDefinition(): array
    {
        $properties = [
            $this->getId(),
            $this->getScore(),
        ];

        if ($this->isNoSave()) {
            $properties[] = 'NOSAVE';
        }

        if ($this->isReplace()) {
            $properties[] = 'REPLACE';
        }

        if (!is_null($this->getLanguage())) {
            $properties[] = 'LANGUAGE';
            $properties[] = $this->getLanguage();
        }

        if (!is_null($this->getPayload())) {
            $properties[] = 'PAYLOAD';
            $properties[] = $this->getPayload();
        }

        $properties[] = 'FIELDS';

        /** @var FieldInterface $field */
        foreach (get_object_vars($this) as $field) {
            if ($field instanceof FieldInterface) {
                $properties[] = $field->getName();
                $properties[] = $field->getValue();
            }
        }
        return $properties;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore($score)
    {
        $this->score = $score;
        return $this;
    }

    public function isNoSave(): bool
    {
        return $this->noSave;
    }

    public function setNoSave(bool $noSave): Document
    {
        $this->noSave = $noSave;
        return $this;
    }

    public function isReplace(): bool
    {
        return $this->replace;
    }

    public function setReplace(bool $replace): Document
    {
        $this->replace = $replace;
        return $this;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }
}
