<?php

namespace Ehann\Tests\Stubs;

use Ehann\RediSearch\Document\Document;
use Ehann\RediSearch\Fields\NumericField;
use Ehann\RediSearch\Fields\TextField;

/**
 * @property TextField title
 * @property TextField author
 * @property NumericField price
 * @property NumericField stock
 */
class TestDocument extends Document
{
}
