<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RedisRaw\Exceptions\RedisRawCommandException;
use PHPUnit\Framework\TestCase;

class RedisRawCommandExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage()
    {
        $command = 'FT.SEARCH MyIndex foo';
        $expected = "Redis Raw Command Failed. $command";
        $subject = new RedisRawCommandException($command);

        $message = $subject->getMessage();

        $this->assertEquals($expected, $message);
    }
}
