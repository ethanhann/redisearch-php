<?php

namespace Eeh\Tests\Stubs;

use Eeh\Redisearch\Document\Document;
use Eeh\Redisearch\Fields\NumericField;
use Eeh\Redisearch\Fields\TextField;

/**
 * @property TextField title
 * @property TextField author
 * @property NumericField price
 * @property NumericField stock
 */
class TestDocument extends Document
{
}
