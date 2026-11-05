<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\InsertOrIgnoreTestCase;

class SqliteInsertOrIgnoreTest extends InsertOrIgnoreTestCase
{
	use SqliteConnectionTrait;
}
