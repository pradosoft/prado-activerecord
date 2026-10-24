<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlsrv;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\BlogRecordTestCase;

class SqlsrvBlogRecordTest extends BlogRecordTestCase
{
	use SqlsrvConnectionTrait;
}
