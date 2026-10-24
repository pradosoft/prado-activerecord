<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlsrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\CountRecordsTestCase;

class SqlsrvCountRecordsTest extends CountRecordsTestCase
{
	use SqlsrvConnectionTrait;
}
