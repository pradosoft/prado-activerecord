<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\SqlSrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\DeleteByPkTestCase;

class SqlSrvDeleteByPkTest extends DeleteByPkTestCase
{
	use SqlSrvConnectionTrait;
}
