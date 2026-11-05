<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\InsertOrIgnoreTestCase;

class PgsqlInsertOrIgnoreTest extends InsertOrIgnoreTestCase
{
	use PgsqlConnectionTrait;
}
