<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\SqlSrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\BlogRecordTestCase;

class SqlSrvBlogRecordTest extends BlogRecordTestCase
{
	use SqlSrvConnectionTrait;
}
