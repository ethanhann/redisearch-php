<?php

namespace Ehann\RediSearch\Document;

use Ehann\RediSearch\Exceptions\FieldNotInSchemaException;
use Ehann\RediSearch\Fields\FieldFactory;
use Ehann\RediSearch\Fields\FieldInterface;

abstract class AbstractDocumentFactory
{
    public static function make(string $id): DocumentInterface
    {
        return new Document($id);
    }

    public static function makeFromArray(array $fields, array $availableSchemaFields, $id = null): DocumentInterface
    {
        $document = new Document($id);
        foreach ($fields as $index => $field) {
            if ($field instanceof FieldInterface) {
                if (!in_array($field->getName(), array_keys($availableSchemaFields))) {
                    throw new FieldNotInSchemaException($field->getName());
                }
                $document->{$field->getName()} = $field;
            } elseif (is_string($index)) {
                if (!isset($availableSchemaFields[$index])) {
                    throw new FieldNotInSchemaException($index);
                }
                $fieldType = $availableSchemaFields[$index];
                $document->{$index} = ($fieldType instanceof FieldInterface) ?
                    $fieldType->setValue($field) :
                    FieldFactory::make($index, $field);
            }
        }
        return $document;
    }
}
