<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;

abstract class ActiveRecordDynamicCallTestCase extends ActiveRecordDbTestCase
{
	public function test_multiple_field_and_or()
	{
		$this->requireFeature('null_parameters', 'The driver rejects a NULL bound as PDO::PARAM_NULL; the description parameter is NULL.');
		$finder = DepartmentRecord::finder();
		$r2 = $finder->findAllByName_And_Description_Or_Active_Or_Sort_Key('Facilities', null, false, 1);
		$this->assertNotNull($r2);
	}

	public function test_dynamic_call()
	{
		$finder = DepartmentRecord::finder();
		$r2 = $finder->findByName('Facilities');
		$this->assertNotNull($r2);
	}

	public function test_dynamic_multiple_field_call()
	{
		$finder = DepartmentRecord::finder();
		$rs = $finder->findByNameAndActive('Marketing', true);
		$this->assertNotNull($rs);
	}

	public function test_dynamic_call_missing_parameters_throws_exception()
	{
		$finder = DepartmentRecord::finder();
		self::expectException(\Prado\Exceptions\TDbException::class);
		$rs = $finder->findByNameAndActive('Marketing');
	}

	public function test_dynamic_call_extras_parameters_ok()
	{
		$finder = DepartmentRecord::finder();
		try {
			$finder->findByNameAndActive('Marketing', true, true);
		} catch (\Prado\Exceptions\TDbException | \PDOException $e) {
			// TDbCommand wraps the error at execution; pdo_oci raises it at bind time.
			$this->addToAssertionCount(1);
			return;
		}
		$this->fail('An extra parameter must raise a database exception.');
	}

	public function test_dynamic_delete_by()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEquals($finder->deleteByName('tasds'), 0);
	}
}
