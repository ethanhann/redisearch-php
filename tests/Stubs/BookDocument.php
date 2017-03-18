<?php

namespace Eeh\Tests\Stubs;

use Eeh\Redisearch\Document;
use Eeh\Redisearch\Fields\FieldInterface;

/**
 * @property FieldInterface title
 * @property FieldInterface author
 * @property FieldInterface price
 * @property FieldInterface stock
 */
class BookDocument extends Document
{
}
