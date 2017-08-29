<?php

namespace Ehann\Tests\RediSearch\Fields;

use Ehann\RediSearch\Fields\FieldFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FieldFactoryTest extends TestCase
{
    public function testShouldGetCorrectType()
    {
        $this->expectException(InvalidArgumentException::class);

        FieldFactory::make('SOME_NON_EXISTING_TYPE', new \stdClass());
    }
}
