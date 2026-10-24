<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepSections;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;
use Prado\Test\Unit\Data\ActiveRecord\Records\SimpleUser;

/**
 * Covers the record life cycle (insert, delete, the deleted state), the delete forms
 * that take several keys, record comparison and export, and criteria objects that the
 * finders leave unchanged. The inserted simple_users rows are removed by each test.
 */
abstract class RecordStateTestCase extends ActiveRecordDbTestCase
{
	private function insertUsers(string ...$names): void
	{
		foreach ($names as $name) {
			$this->assertTrue((new SimpleUser(['username' => $name]))->save());
		}
	}

	private function removeUsers(string ...$names): void
	{
		SimpleUser::finder()->deleteByPk($names);
	}

	public function test_delete_all_by_pks_takes_arguments_or_array()
	{
		$this->insertUsers('state_a', 'state_b', 'state_c');
		try {
			$this->assertEquals(2, SimpleUser::finder()->deleteAllByPks('state_a', 'state_b'));
			$this->assertEquals(1, SimpleUser::finder()->deleteAllByPks(['state_c']));
			$this->assertNull(SimpleUser::finder()->findByPk('state_c'));
		} finally {
			$this->removeUsers('state_a', 'state_b', 'state_c');
		}
	}

	public function test_dynamic_delete_all_by()
	{
		$this->insertUsers('state_d');
		try {
			$this->assertEquals(0, SimpleUser::finder()->deleteAllBy_Username('state_none'));
			$this->assertEquals(1, SimpleUser::finder()->deleteAllByUsername('state_d'));
		} finally {
			$this->removeUsers('state_d');
		}
	}

	public function test_deleted_record_cannot_be_saved_or_deleted_again()
	{
		$this->insertUsers('state_e');
		$user = SimpleUser::finder()->findByPk('state_e');
		try {
			$this->assertTrue($user->delete());
			$this->assertNull(SimpleUser::finder()->findByPk('state_e'));
			try {
				$user->save();
				$this->fail('save() of a deleted record must throw.');
			} catch (TActiveRecordException $e) {
				$this->assertStringContainsString('SimpleUser', $e->getMessage());
			}
			$this->expectException(TActiveRecordException::class);
			$user->delete();
		} finally {
			$this->removeUsers('state_e');
		}
	}

	public function test_find_by_composite_pk_arguments()
	{
		$this->requireFeature('composite_pk', 'The driver metadata reports only the first column of a composite primary key.');
		$section = DepSections::finder()->findByPk(1, 2);
		$this->assertNotNull($section);
		$this->assertEquals(2, $section->sort_key);
	}

	public function test_equals_compares_primary_key()
	{
		$first = DepartmentRecord::finder()->findByPk(1);
		$this->assertTrue($first->equals(DepartmentRecord::finder()->findByPk(1)));
		$this->assertFalse($first->equals(DepartmentRecord::finder()->findByPk(2)));
		$this->assertFalse($first->equals(SimpleUser::finder()->findByPk('tom')));
	}

	public function test_to_array_and_json()
	{
		$department = DepartmentRecord::finder()->findByPk(2);
		$data = array_change_key_case($department->toArray());
		$this->assertSame(['department_id', 'name', 'description', 'active', 'sort_key'], array_keys($data));
		$this->assertSame('Marketing', $data['name']);
		$this->assertNull($data['description']);
		$this->assertSame('Marketing', array_change_key_case(json_decode($department->toJSON(), true))['name']);
	}

	public function test_finders_leave_criteria_unchanged()
	{
		$criteria = new TActiveRecordCriteria('department_id > ?', [0]);
		$criteria->OrdersBy['department_id'] = 'asc';
		$this->assertEquals(1, DepartmentRecord::finder()->find($criteria)->department_id);
		$this->assertNull($criteria->getLimit());
		$this->assertCount(8, DepartmentRecord::finder()->findAll($criteria));
		$this->assertSame('department_id > ?', $criteria->getCondition());
	}
}
