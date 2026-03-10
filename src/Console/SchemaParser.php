<?php

namespace Ehann\RediSearch\Console;

use Ehann\RediSearch\Index;

class SchemaParser
{
    /**
     * Parses a JSON schema file and applies field definitions to the given index.
     *
     * @param string $filePath Path to the JSON schema file
     * @param Index $index The index to apply fields to
     * @return Index
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function applySchema(string $filePath, Index $index): Index
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Schema file not found: $filePath");
        }

        $json = file_get_contents($filePath);
        $schema = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in schema file: ' . json_last_error_msg());
        }

        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            throw new \RuntimeException('Schema must contain a "fields" array.');
        }

        foreach ($schema['fields'] as $field) {
            if (!isset($field['name'], $field['type'])) {
                throw new \RuntimeException('Each field must have a "name" and "type".');
            }

            $name = $field['name'];
            $type = strtoupper($field['type']);
            $sortable = $field['sortable'] ?? false;
            $noindex = $field['noindex'] ?? false;

            match ($type) {
                'TEXT' => $index->addTextField(
                    $name,
                    (float) ($field['weight'] ?? 1.0),
                    $sortable,
                    $noindex
                ),
                'NUMERIC' => $index->addNumericField($name, $sortable, $noindex),
                'TAG' => $index->addTagField(
                    $name,
                    $sortable,
                    $noindex,
                    $field['separator'] ?? ','
                ),
                'GEO' => $index->addGeoField($name, $noindex),
                'VECTOR' => $index->addVectorField(
                    $name,
                    $field['algorithm'] ?? 'FLAT',
                    $field['vectorType'] ?? 'FLOAT32',
                    (int) ($field['dim'] ?? 128),
                    $field['distanceMetric'] ?? 'COSINE',
                    $field['extraAttributes'] ?? []
                ),
                default => throw new \RuntimeException("Unknown field type: $type"),
            };
        }

        return $index;
    }
}
