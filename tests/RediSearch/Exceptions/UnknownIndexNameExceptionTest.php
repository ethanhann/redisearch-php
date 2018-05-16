<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RedisRaw\Exceptions\UnknownIndexNameException;
use PHPUnit\Framework\TestCase;

class UnknownIndexNameExceptionTest extends TestCase
{
    public function testShouldShowCustomMessage()
    {
        $indexName = 'MyIndex';
        $expected = "Unknown index name. $indexName";
        $subject = new UnknownIndexNameException($indexName);

        $message = $subject->getMessage();

        $this->assertEquals($expected, $message);
    }
}
