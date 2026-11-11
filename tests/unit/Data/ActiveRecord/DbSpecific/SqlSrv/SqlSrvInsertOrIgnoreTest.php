<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\SqlSrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\InsertOrIgnoreTestCase;

class SqlSrvInsertOrIgnoreTest extends InsertOrIgnoreTestCase
{
	use SqlSrvConnectionTrait;
}
