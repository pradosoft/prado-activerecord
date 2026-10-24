<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\UserRecordTestCase;

class SqliteUserRecordTest extends UserRecordTestCase
{
	use SqliteConnectionTrait;
}
