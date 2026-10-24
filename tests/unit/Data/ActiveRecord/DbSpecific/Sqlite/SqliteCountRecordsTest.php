<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\CountRecordsTestCase;

class SqliteCountRecordsTest extends CountRecordsTestCase
{
	use SqliteConnectionTrait;
}
