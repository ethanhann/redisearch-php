<?php

namespace Ehann\Tests\RediSearch\Exceptions;

use Ehann\RediSearch\Exceptions\InvalidRedisClientClassException;
use PHPUnit\Framework\TestCase;

class InvalidRedisClientClassExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage()
    {
        $expected = 'Only Predis\Client and Redis client classes are allowed.';

        $message = (new InvalidRedisClientClassException())->getMessage();

        $this->assertEquals($expected, $message);
    }
}
