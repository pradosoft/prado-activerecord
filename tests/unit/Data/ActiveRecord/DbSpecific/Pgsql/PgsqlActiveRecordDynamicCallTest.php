<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\ActiveRecordDynamicCallTestCase;

class PgsqlActiveRecordDynamicCallTest extends ActiveRecordDynamicCallTestCase
{
	use PgsqlConnectionTrait;
}
