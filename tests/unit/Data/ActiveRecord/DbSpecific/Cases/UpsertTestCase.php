<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Exceptions\TDbException;

/**
 * Covers TActiveRecord::upsert() with UpsertRecord::CONFLICT_COLUMNS targeting the
 * unique username: a new row is inserted; a conflicting row is updated as the update
 * data selects; other rows are unchanged. OnInsert is raised on both paths and can
 * cancel the write.
 */
abstract class UpsertTestCase extends ConflictWriteTestCase
{
	public function test_new_row_is_inserted_and_record_loaded()
	{
		$record = $this->newRecord('alice', 42);

		$result = $this->write(fn () => $record->upsert());

		$this->assertNotFalse($result);
		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
		$this->assertSame(42, (int) $this->found('alice')->score);
	}

	public function test_new_row_sets_the_identity()
	{
		$this->requireFeature('identity', 'The driver does not read an identity value back after an insert.');
		$record = $this->newRecord('alice', 1);

		$this->write(fn () => $record->upsert());

		$this->assertEquals($this->found('alice')->upsert_id, $record->upsert_id);
	}

	public function test_conflict_updates_every_other_column()
	{
		$this->seed('alice', 10);
		$record = $this->newRecord('alice', 99, 'updated');

		$this->assertNotFalse($this->write(fn () => $record->upsert()));

		$found = $this->found('alice');
		$this->assertSame(99, (int) $found->score);
		$this->assertSame('updated', $found->note);
		$this->assertSame(1, $this->rowCount());
		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_conflict_updates_the_listed_columns_from_the_record()
	{
		$this->seed('alice', 10);

		$this->write(fn () => $this->newRecord('alice', 77, 'ignored')->upsert(['score']));

		$found = $this->found('alice');
		$this->assertSame(77, (int) $found->score);
		$this->assertNull($found->note);
	}

	public function test_conflict_updates_columns_to_given_values()
	{
		$this->seed('alice', 10);

		$this->write(fn () => $this->newRecord('alice', 55)->upsert(['score' => 99]));

		$this->assertSame(99, (int) $this->found('alice')->score);
	}

	public function test_conflict_updates_listed_and_given_columns()
	{
		$this->seed('alice', 10);

		$this->write(fn () => $this->newRecord('alice', 42)->upsert(['score', 'note' => 'given']));

		$found = $this->found('alice');
		$this->assertSame(42, (int) $found->score);
		$this->assertSame('given', $found->note);
	}

	public function test_empty_update_data_leaves_a_conflicting_row()
	{
		$this->seed('alice', 10);

		$this->write(fn () => $this->newRecord('alice', 99)->upsert([]));

		$this->assertSame(10, (int) $this->found('alice')->score);
	}

	public function test_other_rows_are_unchanged()
	{
		$this->seed('alice', 10);
		$this->seed('bob', 20);

		$this->write(fn () => $this->newRecord('alice', 99)->upsert());

		$this->assertSame(20, (int) $this->found('bob')->score);
	}

	public function test_oninsert_is_raised_on_conflict()
	{
		$this->seed('alice', 10);
		$record = $this->newRecord('alice', 99);
		$raised = false;
		$record->OnInsert[] = function () use (&$raised) {
			$raised = true;
		};

		$this->write(fn () => $record->upsert());

		$this->assertTrue($raised);
	}

	public function test_oninsert_cancels_the_write()
	{
		$this->seed('alice', 10);
		$record = $this->newRecord('alice', 99);
		$record->OnInsert[] = fn ($sender, $param) => $param->setIsValid(false);

		$this->assertFalse($this->write(fn () => $record->upsert()));
		$this->assertSame(10, (int) $this->found('alice')->score);
	}

	public function test_merge_requires_a_transaction()
	{
		if (!in_array('merge_transaction', $this->getUnsupportedFeatures(), true)) {
			$this->markTestSkipped('The driver does not build a MERGE statement.');
		}
		$this->expectException(TDbException::class);
		$this->newRecord('alice', 1)->upsert();
	}
}
