<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlsrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\InsertOrIgnoreTestCase;

class SqlsrvInsertOrIgnoreTest extends InsertOrIgnoreTestCase
{
	use SqlsrvConnectionTrait;
}
