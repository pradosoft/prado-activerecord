<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlsrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\DeleteByPkTestCase;

class SqlsrvDeleteByPkTest extends DeleteByPkTestCase
{
	use SqlsrvConnectionTrait;
}
