<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\BlogRecordTestCase;

class SqliteBlogRecordTest extends BlogRecordTestCase
{
	use SqliteConnectionTrait;
}
