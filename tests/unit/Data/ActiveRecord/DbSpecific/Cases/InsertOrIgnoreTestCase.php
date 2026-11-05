<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Exceptions\TDbException;

/**
 * Covers TActiveRecord::insertOrIgnore(): a new row is inserted and the record is
 * loaded; a row that conflicts on the unique username is left unchanged, the call
 * returns false, and the record stays new. OnInsert is raised and can cancel the insert.
 */
abstract class InsertOrIgnoreTestCase extends ConflictWriteTestCase
{
	public function test_new_row_is_inserted_and_record_loaded()
	{
		$record = $this->newRecord('alice', 42);
		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState());

		$result = $this->write(fn () => $record->insertOrIgnore());

		$this->assertNotFalse($result);
		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
		$this->assertSame(42, (int) $this->found('alice')->score);
	}

	public function test_new_row_sets_the_identity()
	{
		$this->requireFeature('identity', 'The driver does not read an identity value back after an insert.');
		$record = $this->newRecord('alice', 1);

		$this->write(fn () => $record->insertOrIgnore());

		$this->assertGreaterThan(0, (int) $record->upsert_id);
		$this->assertEquals($this->found('alice')->upsert_id, $record->upsert_id);
	}

	public function test_conflict_returns_false_and_leaves_the_row()
	{
		$this->seed('alice', 10);
		$duplicate = $this->newRecord('alice', 99);

		$result = $this->write(fn () => $duplicate->insertOrIgnore());

		$this->assertFalse($result);
		$this->assertSame(TActiveRecord::STATE_NEW, $duplicate->getRecordState());
		$this->assertSame(10, (int) $this->found('alice')->score);
		$this->assertSame(1, $this->rowCount());
	}

	public function test_oninsert_is_raised()
	{
		$record = $this->newRecord('alice', 1);
		$raised = null;
		$record->OnInsert[] = function ($sender, $param) use (&$raised) {
			$raised = $param;
		};

		$this->write(fn () => $record->insertOrIgnore());

		$this->assertInstanceOf(TActiveRecordChangeEventParameter::class, $raised);
	}

	public function test_oninsert_cancels_the_insert()
	{
		$record = $this->newRecord('alice', 1);
		$record->OnInsert[] = fn ($sender, $param) => $param->setIsValid(false);

		$this->assertFalse($this->write(fn () => $record->insertOrIgnore()));
		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState());
		$this->assertSame(0, $this->rowCount());
	}

	public function test_merge_requires_a_transaction()
	{
		if (!in_array('merge_transaction', $this->getUnsupportedFeatures(), true)) {
			$this->markTestSkipped('The driver does not build a MERGE statement.');
		}
		$this->expectException(TDbException::class);
		$this->newRecord('alice', 1)->insertOrIgnore();
	}
}
