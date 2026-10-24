<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\CountRecordsTestCase;

class PgsqlCountRecordsTest extends CountRecordsTestCase
{
	use PgsqlConnectionTrait;
}
