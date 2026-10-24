<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;

abstract class FindBySqlTestCase extends ActiveRecordDbTestCase
{
	public function test_find_by_sql()
	{
		$deps = DepartmentRecord::finder()->findBySql('SELECT * FROM departments');
		$this->assertNotNull($deps);
	}

	public function test_find_all_by_sql_populates_records()
	{
		$deps = DepartmentRecord::finder()->findAllBySql('SELECT * FROM departments WHERE department_id < ?', 3);
		$this->assertCount(2, $deps);
		$this->assertInstanceOf(DepartmentRecord::class, $deps[0]);
		$this->assertEquals(1, $deps[0]->department_id);
		$this->assertEquals('Facilities', $deps[0]->name);
	}
}
