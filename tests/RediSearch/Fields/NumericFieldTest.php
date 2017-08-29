<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Fields\NumericField;
use PHPUnit\Framework\TestCase;

class NumericFieldTest extends TestCase
{
    public function testShouldGetCorrectType()
    {
        $expected = 'NUMERIC';

        $type = (new NumericField('MyNumericField'))->getType();

        $this->assertEquals($expected, $type);
    }
}