<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;

abstract class CountRecordsTestCase extends ActiveRecordDbTestCase
{
	public function test_count()
	{
		$finder = DepartmentRecord::finder();
		$count = $finder->count('sort_key > ?', 2);
		$this->assertTrue($count > 0);
	}

	public function test_count_zero()
	{
		$finder = DepartmentRecord::finder();
		$count = $finder->count('sort_key > ?', 11);
		$this->assertEquals($count, 0);
	}

	public function test_count_without_parameter()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEquals($finder->count(), 8);
	}
}
