<?php

namespace Ehann\Tests\RediSearch\Aggregate;

use Ehann\RediSearch\Aggregate\Builder;
use Ehann\Tests\Stubs\TestIndex;
use Ehann\Tests\AbstractTestCase;

class BuilderTest extends AbstractTestCase
{
    /** @var Builder */
    private $subject;
    private $expectedResult1;
    private $expectedResult2;
    private $expectedResult3;
    private $expectedResult4;

    public function setUp()
    {
        $this->indexName = 'AggregateBuilderTest';
        $index = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title', 1.0, true)
            ->addTextField('author', true)
            ->addNumericField('price', true)
            ->addNumericField('stock', true);
        $index->create();

        $this->expectedResult1 = [
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ];
        $index->add($this->expectedResult1);
        $this->expectedResult2 = [
            'title' => 'How to be awesome, part 2 - Electric Boogaloo',
            'author' => 'Jessica',
            'price' => 18.85,
            'stock' => 32,
        ];
        $index->add($this->expectedResult2);
        $this->expectedResult3 = [
            'title' => 'How to be awesome, part 3, section 13, appendix A',
            'author' => 'Jack',
            'price' => 18.85,
            'stock' => 32,
        ];
        $index->add($this->expectedResult3);
        $this->expectedResult4 = [
            'title' => 'How to be awesome.',
            'author' => 'Barry',
            'price' => 19.99,
            'stock' => 231,
        ];
        $index->add($this->expectedResult4);
        $this->subject = (new Builder($this->redisClient, $this->indexName));
    }

    public function tearDown()
    {
        $this->redisClient->flushAll();
    }

    public function testGetAverageOfNumeric()
    {
        $expectedCount = 3;
        $expectedAveragePrice = 14.99;

        $result = $this->subject->avg(['title'], 'price')->search();

        $this->assertEquals($expectedCount, $result->getCount());
        $this->assertEquals($expectedAveragePrice, $result->getDocuments()[0]->avg_price);
    }

}
