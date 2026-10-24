<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\UserRecord;

abstract class RecordEventTestCase extends ActiveRecordDbTestCase
{
	public function testFindByPk()
	{
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);
	}

	public function test_execute_command_event_fires_on_find()
	{
		$fired = 0;
		$finder = UserRecord::finder();
		$handler = function ($sender, $param) use (&$fired) {
			$fired++;
		};
		$finder->OnExecuteCommand[] = $handler;
		try {
			$finder->findByPk('admin');
		} finally {
			$finder->OnExecuteCommand->remove($handler);
		}
		$this->assertSame(1, $fired);
	}
}
