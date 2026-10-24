<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\UserRecordTestCase;

class PgsqlUserRecordTest extends UserRecordTestCase
{
	use PgsqlConnectionTrait;
}
