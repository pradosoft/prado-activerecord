<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\DeleteByPkTestCase;

class SqliteDeleteByPkTest extends DeleteByPkTestCase
{
	use SqliteConnectionTrait;
}
