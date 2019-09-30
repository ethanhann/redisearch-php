<?php

namespace Ehann\RediSearch\Document;

use Ehann\RediSearch\Exceptions\OutOfRangeDocumentScoreException;
use Ehann\RediSearch\Fields\FieldInterface;

class Document implements DocumentInterface
{
    protected $id;
    protected $score = 1.0;
    protected $noSave = false;
    protected $replace = false;
    protected $partial = false;
    protected $noCreate = false;
    protected $payload;
    protected $language;

    public function __construct($id = null)
    {
        $this->id = $id ?? uniqid(true);
    }

    public function getHashDefinition(): array
    {
        $properties = [
            $this->getId(),
            $this->getScore(),
        ];

        if (!is_null($this->getLanguage())) {
            $properties[] = 'LANGUAGE';
            $properties[] = $this->getLanguage();
        }

        if ($this->isReplace()) {
            $properties[] = 'REPLACE';

          if ($this->isPartial()) {
              $properties[] = 'PARTIAL';
          }

          if ($this->isNoCreate()) {
              $properties[] = 'NOCREATE';
          }
        }

        return $properties;
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

            if ($this->isPartial()) {
                $properties[] = 'PARTIAL';
            }

            if ($this->isNoCreate()) {
               $properties[] = 'NOCREATE';
            }
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
            if ($field instanceof FieldInterface && !is_null($field->getValue())) {
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

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score)
    {
        if ($score < 0.0 || $score > 1.0) {
            throw new OutOfRangeDocumentScoreException();
        }
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

    public function isPartial(): bool
    {
        return $this->partial;
    }

    public function setPartial(bool $partial): Document
    {
        $this->partial = $partial;
        return $this;
    }

    public function isNoCreate(): bool
    {
        return $this->noCreate;
    }

    public function setNoCreate(bool $noCreate): Document
    {
        $this->noCreate = $noCreate;
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
