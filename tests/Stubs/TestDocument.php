<?php

namespace Eeh\Tests\Stubs;

use Eeh\RediSearch\Document\Document;
use Eeh\RediSearch\Fields\NumericField;
use Eeh\RediSearch\Fields\TextField;

/**
 * @property TextField title
 * @property TextField author
 * @property NumericField price
 * @property NumericField stock
 */
class TestDocument extends Document
{
}
