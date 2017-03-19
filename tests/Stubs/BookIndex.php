<?php

namespace Eeh\Tests\Stubs;

use Eeh\Redisearch\AbstractIndex;
use Eeh\Redisearch\Fields\NumericField;
use Eeh\Redisearch\Fields\TextField;
use Eeh\Redisearch\Query\BuilderInterface;
use Eeh\Redisearch\SearchResult;

class BookIndex extends AbstractIndex
{
    public $title;
    public $author;
    public $price;
    public $stock;

    public function __construct()
    {
        $this->title = new TextField('title');
        $this->author = new TextField('author');
        $this->price = new NumericField('price');
        $this->stock = new NumericField('stock');
    }
}
