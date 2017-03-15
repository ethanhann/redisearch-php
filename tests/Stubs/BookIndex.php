<?php

namespace Eeh\Tests\Stubs;

use Eeh\Redisearch\AbstractIndex;
use Eeh\Redisearch\Fields\TextField;

class BookIndex extends AbstractIndex
{
    public $title;
    public $author;

    public function __construct()
    {
        $this->title = new TextField('title');
        $this->author = new TextField('author');
    }
}
