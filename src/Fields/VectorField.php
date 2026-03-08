<?php

namespace Ehann\RediSearch\Fields;

/**
 * Represents a VECTOR field in a RediSearch index. Available in RediSearch v2.2+.
 *
 * Supports FLAT (brute-force) and HNSW (hierarchical navigable small world graph) algorithms
 * for approximate/exact nearest-neighbor search.
 *
 * Example:
 *   $index->addVectorField('embedding', VectorField::ALGORITHM_HNSW, VectorField::TYPE_FLOAT32, 128, VectorField::DISTANCE_COSINE);
 */
class VectorField extends AbstractField
{
    public const ALGORITHM_FLAT = 'FLAT';
    public const ALGORITHM_HNSW = 'HNSW';

    public const TYPE_FLOAT32 = 'FLOAT32';
    public const TYPE_FLOAT64 = 'FLOAT64';

    public const DISTANCE_L2 = 'L2';
    public const DISTANCE_IP = 'IP';
    public const DISTANCE_COSINE = 'COSINE';

    private string $algorithm;
    private string $type;
    private int $dim;
    private string $distanceMetric;
    private array $extraAttributes;

    public function __construct(
        string $name,
        string $algorithm = self::ALGORITHM_FLAT,
        string $type = self::TYPE_FLOAT32,
        int $dim = 128,
        string $distanceMetric = self::DISTANCE_COSINE,
        array $extraAttributes = []
    ) {
        parent::__construct($name);
        $this->algorithm = $algorithm;
        $this->type = $type;
        $this->dim = $dim;
        $this->distanceMetric = $distanceMetric;
        $this->extraAttributes = $extraAttributes;
    }

    public function getType(): string
    {
        return 'VECTOR';
    }

    public function getTypeDefinition(): array
    {
        // Base attributes: TYPE, DIM, DISTANCE_METRIC (3 pairs = 6 values)
        $attributes = [
            'TYPE', $this->type,
            'DIM', $this->dim,
            'DISTANCE_METRIC', $this->distanceMetric,
        ];

        // Flatten extra attributes (key => value pairs)
        foreach ($this->extraAttributes as $key => $value) {
            $attributes[] = strtoupper($key);
            $attributes[] = $value;
        }

        $attributeCount = count($attributes);

        return array_merge(
            [$this->getName(), 'VECTOR', $this->algorithm, $attributeCount],
            $attributes
        );
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getDim(): int
    {
        return $this->dim;
    }

    public function getDistanceMetric(): string
    {
        return $this->distanceMetric;
    }
}
