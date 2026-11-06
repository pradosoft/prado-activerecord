<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\UpsertTestCase;

class PgsqlUpsertTest extends UpsertTestCase
{
	use PgsqlConnectionTrait;
}
