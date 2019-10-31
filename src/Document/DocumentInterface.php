<?php

namespace Ehann\RediSearch\Document;

interface DocumentInterface
{
    public function getHashDefinition(): array;
    public function getDefinition(): array;
    public function getId(): string;
    public function setId(string $id);
    public function getScore(): float;
    public function setScore(float $score);
    public function isNoSave(): bool;
    public function setNoSave(bool $noSave): Document;
    public function isReplace(): bool;
    public function setReplace(bool $replace): Document;
    public function isPartial(): bool;
    public function setPartial(bool $partial): Document;
    public function isNoCreate(): bool;
    public function setNoCreate(bool $noCreate): Document;
    public function getPayload();
    public function setPayload($payload);
    public function getLanguage();
    public function setLanguage($language);
}
