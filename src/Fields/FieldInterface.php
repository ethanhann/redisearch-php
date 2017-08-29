<?php

namespace Ehann\RediSearch\Fields;

interface FieldInterface
{
    public function getTypeDefinition(): array;
    public function getValueDefinition(): array;
    public function getType(): string;
    public function getName(): string;
    public function getValue();
    public function isSortable(): bool;
    public function setValue($value);
}
