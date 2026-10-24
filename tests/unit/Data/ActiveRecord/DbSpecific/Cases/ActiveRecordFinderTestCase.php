<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;

abstract class ActiveRecordFinderTestCase extends ActiveRecordDbTestCase
{
	public function testFindBySQL_returns_one_record()
	{
		$department = DepartmentRecord::finder()->find('department_id < ?', 5);
		$this->assertNotNull($department);
	}

	public function testFindBySQL_returns_zero_records()
	{
		$department = DepartmentRecord::finder()->find('department_id > ?', 15);
		$this->assertNull($department);
	}

	public function test_find_by_sql_returns_iterator()
	{
		$deps = DepartmentRecord::finder()->findAll('department_id < :id', ['id' => 5]);
		$this->assertEquals(count($deps), 4);
	}

	public function test_find_by_multiple_parameters()
	{
		$department = DepartmentRecord::finder()->find('department_id < ? AND sort_key > ?', 5, 2);
		$this->assertNotNull($department);
	}

	public function test_find_by_array_parameter()
	{
		$department = DepartmentRecord::finder()->find('department_id < ? AND sort_key > ?', [5, 2]);
		$this->assertNotNull($department);
	}
}
