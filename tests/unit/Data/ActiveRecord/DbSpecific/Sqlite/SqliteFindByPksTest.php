<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\FindByPksTestCase;

class SqliteFindByPksTest extends FindByPksTestCase
{
	use SqliteConnectionTrait;
}
