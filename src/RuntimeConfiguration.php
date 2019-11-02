<?php

namespace Ehann\RediSearch;

class RuntimeConfiguration extends AbstractRediSearchClientAdapter
{
    protected function getOption($name)
    {
        return $this->rawCommand('FT.CONFIG', ['GET', $name]);
    }

    protected function setOption($name, $value)
    {
        return $this->rawCommand('FT.CONFIG', ['SET', $name, $value]);
    }

    protected function convertRawResponseToString(array $rawResponse): string
    {
        $value = $rawResponse[0][1];
        if (is_object($value) && method_exists($value, 'getPayload')) {
            $value = $value->getPayload();
        }
        return $value;
    }

    protected function convertRawResponseToInt($rawResponse): int
    {
        return intval($this->convertRawResponseToString($rawResponse));
    }

    public function getMinPrefix(): int
    {
        return $this->convertRawResponseToInt($this->getOption('MINPREFIX'));
    }

    public function setMinPrefix(int $value = 2)
    {
        return $this->setOption('MINPREFIX', $value);
    }

    public function getMaxExpansions(): int
    {
        return $this->convertRawResponseToInt($this->getOption('MAXEXPANSIONS'));
    }

    public function setMaxExpansions(int $value = 200): bool
    {
        return $this->setOption('MAXEXPANSIONS', $value);
    }

    public function getTimeoutInMilliseconds(): int
    {
        return $this->convertRawResponseToInt($this->getOption('TIMEOUT'));
    }

    public function setTimeoutInMilliseconds(int $value = 500)
    {
        return $this->setOption('TIMEOUT', $value);
    }

    public function isOnTimeoutPolicyReturn(): bool
    {
        return $this->convertRawResponseToString($this->getOption('ON_TIMEOUT')) === 'return';
    }

    public function isOnTimeoutPolicyFail(): bool
    {
        return $this->convertRawResponseToString($this->getOption('ON_TIMEOUT')) === 'fail';
    }

    public function setOnTimeoutPolicyToReturn(): bool
    {
        return $this->setOption('ON_TIMEOUT', 'return');
    }

    public function setOnTimeoutPolicyToFail(): bool
    {
        return $this->setOption('ON_TIMEOUT', 'fail');
    }

    public function getMinPhoneticTermLength(): int
    {
        return $this->convertRawResponseToInt($this->getOption('MIN_PHONETIC_TERM_LEN'));
    }

    public function setMinPhoneticTermLength(int $value = 3): bool
    {
        return $this->setOption('MIN_PHONETIC_TERM_LEN', $value);
    }
}
