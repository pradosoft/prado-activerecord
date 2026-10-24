<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases\FindByPksTestCase;

class PgsqlFindByPksTest extends FindByPksTestCase
{
	use PgsqlConnectionTrait;
}
