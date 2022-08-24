<?php

namespace Ehann\RediSearch;

class Suggestion extends AbstractIndex
{
    /**
     * Add a suggestion string to an auto-complete suggestion dictionary.
     * This is disconnected from the index definitions,
     * and leaves creating and updating suggestion dictionaries to the user.
     *
     * @param string $string
     * @param float $score
     * @param bool $increment
     * @param null $payload
     * @return int
     */
    public function add(string $string, float $score, bool $increment = false, $payload = null)
    {
        $args = [
            $this->indexName,
            $string,
            $score
        ];
        if ($increment) {
            $args[] = 'INCR';
        }
        if (!is_null($payload)) {
            $args[] = 'PAYLOAD';
            $args[] = $payload;
        }
        return $this->rawCommand('FT.SUGADD', $args);
    }

    /**
     * Delete a string from a suggestion index.
     *
     * @param string $string
     * @return bool
     */
    public function delete(string $string): bool
    {
        return $this->rawCommand('FT.SUGDEL', [$this->indexName, $string]) === 1;
    }

    /**
     * Get the size of an auto-complete suggestion dictionary.
     *
     * @return int
     */
    public function length(): int
    {
        return $this->rawCommand('FT.SUGLEN', [$this->indexName]);
    }

    /**
     * Get completion suggestions for a prefix.
     *
     * @param string $prefix
     * @param bool $fuzzy
     * @param bool $withPayloads
     * @param bool $withScores
     * @param int $max
     * @return array
     */
    public function get(string $prefix, bool $fuzzy = false, bool $withPayloads = false, int $max = -1, bool $withScores = false): array
    {
        $args = [
            $this->indexName,
            $prefix,
        ];
        if ($fuzzy) {
            $args[] = 'FUZZY';
        }
        if ($withPayloads) {
            $args[] = 'WITHPAYLOADS';
        }
        if ($withScores) {
            $args[] = 'WITHSCORES';
        }
        if ($max >= 0) {
            $args[] = 'MAX';
            $args[] = $max;
        }
        return $this->rawCommand('FT.SUGGET', $args);
    }
}
