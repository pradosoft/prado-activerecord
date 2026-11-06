<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\SqlSrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\CountRecordsTestCase;

class SqlSrvCountRecordsTest extends CountRecordsTestCase
{
	use SqlSrvConnectionTrait;
}
