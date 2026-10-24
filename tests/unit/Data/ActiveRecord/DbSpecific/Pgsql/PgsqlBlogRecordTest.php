<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\BlogRecordTestCase;

class PgsqlBlogRecordTest extends BlogRecordTestCase
{
	use PgsqlConnectionTrait;
}
