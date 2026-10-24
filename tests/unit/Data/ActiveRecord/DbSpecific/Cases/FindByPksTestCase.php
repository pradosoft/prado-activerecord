<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepSections;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;

abstract class FindByPksTestCase extends ActiveRecordDbTestCase
{
	public function test_find_by_1pk()
	{
		$dep = DepartmentRecord::finder()->findByPk(1);
		$this->assertNotNull($dep);
		$this->assertEquals($dep->department_id, 1);
	}

	public function test_find_by_1pk_array()
	{
		$dep = DepartmentRecord::finder()->findByPk([1]);
		$this->assertNotNull($dep);
		$this->assertEquals($dep->department_id, 1);
	}

	public function test_find_by_pks()
	{
		$deps = DepartmentRecord::finder()->findAllByPks(1, 2, 4);
		$this->assertEquals(count($deps), 3);
		$this->assertEquals($deps[0]->department_id, 1);
		$this->assertEquals($deps[1]->department_id, 2);
		$this->assertEquals($deps[2]->department_id, 4);
	}

	public function test_find_by_pks_with_invalid()
	{
		$deps = DepartmentRecord::finder()->findAllByPks(4, 2, 14);
		$this->assertEquals(count($deps), 2);
		$this->assertEquals($deps[0]->department_id, 2);
		$this->assertEquals($deps[1]->department_id, 4);
	}

	public function test_find_by_composite_pks()
	{
		$this->requireFeature('composite_pk', 'The driver metadata reports only the first column of a composite primary key.');
		$this->requireFeature('composite_pk_in', 'The driver does not accept the (a, b) IN ((1, 2)) form the gateway builds for a composite primary key.');
		$ds = DepSections::finder()->findAllByPks([1, 1], [2, 5]);
		$this->assertEquals(count($ds), 2);
		$this->assertIsDepSection($ds[0], 1, 1);
		$this->assertIsDepSection($ds[1], 2, 5);
	}

	public function assertIsDepSection($dep, $dep_id, $sec_id)
	{
		$this->assertTrue($dep instanceof DepSections);
		$this->assertEquals($dep->department_id, $dep_id);
		$this->assertEquals($dep->section_id, $sec_id);
	}
}
