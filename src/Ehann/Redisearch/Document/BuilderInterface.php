<?php

namespace Ehann\RediSearch\Document;

interface BuilderInterface
{
    public function add($document): bool;
    public function addMany(array $documents, $disableAtomicity = false);
    public function replace($document): bool;
    public function id(string $id): BuilderInterface;
    public function score($score): BuilderInterface;
    public function noSave(): BuilderInterface;
    public function payload($payload): BuilderInterface;
    public function language($language): BuilderInterface;
}
