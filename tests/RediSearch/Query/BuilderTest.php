<?php

namespace Ehann\Tests\RediSearch;

use Ehann\RediSearch\Query\Builder;
use Ehann\RediSearch\Redis\RedisClient;
use Ehann\Tests\Stubs\TestIndex;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    private $indexName;
    /** @var Builder */
    private $subject;
    /** @var RedisClient */
    private $redisClient;

    public function setUp()
    {
        $this->indexName = 'QueryBuilderTest';
        $this->redisClient = new RedisClient(
            \Redis::class,
            getenv('REDIS_HOST') ?? '127.0.0.1',
            getenv('REDIS_PORT') ?? 6379,
            getenv('REDIS_DB') ?? 0
        );
        $index = (new TestIndex($this->redisClient, $this->indexName))
            ->addTextField('title')
            ->addTextField('author')
            ->addNumericField('price')
            ->addNumericField('stock');
        $index->create();
        $index->makeDocument();
        $index->add([
            'title' => 'How to be awesome.',
            'author' => 'Jack',
            'price' => 9.99,
            'stock' => 231,
        ]);
        $index->add([
            'title' => 'Shoes in the 22st Century',
            'author' => 'Jessica',
            'price' => 18.85,
            'stock' => 32,
        ]);
        $this->subject = (new Builder($this->redisClient, $this->indexName));
    }

    public function tearDown()
    {
        $this->redisClient->flushAll();
    }

    public function testSearch()
    {
        $result = $this->subject->search('awesome');

        $this->assertTrue($result->getCount() === 1);
    }

    public function testSearchWithScores()
    {
        $result = $this->subject->withScores()->search('awesome');

        $this->assertTrue($result->getCount() === 1);
    }
}
