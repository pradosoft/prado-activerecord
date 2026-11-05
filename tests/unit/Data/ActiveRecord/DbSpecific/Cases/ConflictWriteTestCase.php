<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Cases;

use Prado\Test\Unit\Data\ActiveRecord\DbSpecific\ActiveRecordDbTestCase;
use Prado\Test\Unit\Data\ActiveRecord\Records\UpsertRecord;

/**
 * Base of the insertOrIgnore() and upsert() cases over the upsert_test table.
 *
 * Each test starts with an empty table. A driver that lists `merge_transaction`
 * builds a MERGE statement, which needs an active transaction; {@see write()} opens one.
 */
abstract class ConflictWriteTestCase extends ActiveRecordDbTestCase
{
	protected function getTestTables(): array
	{
		return [UpsertRecord::TABLE];
	}

	protected function getRecordClasses(): array
	{
		return [...parent::getRecordClasses(), UpsertRecord::class];
	}

	protected function setUp(): void
	{
		parent::setUp();
		static::$connection?->createCommand('DELETE FROM upsert_test')->execute();
	}

	/**
	 * Runs a write, in a transaction when the driver needs one for MERGE.
	 * @param callable $write the write.
	 * @return mixed the write's result.
	 */
	protected function write(callable $write): mixed
	{
		if (!in_array('merge_transaction', $this->getUnsupportedFeatures(), true)) {
			return $write();
		}
		$transaction = static::$connection->beginTransaction();
		try {
			$result = $write();
			$transaction->commit();
			return $result;
		} catch (\Throwable $e) {
			$transaction->rollback();
			throw $e;
		}
	}

	protected function newRecord(string $username, int $score = 0, ?string $note = null): UpsertRecord
	{
		return new UpsertRecord(['username' => $username, 'score' => $score, 'note' => $note]);
	}

	/**
	 * Inserts a row directly, bypassing the record.
	 */
	protected function seed(string $username, int $score): void
	{
		static::$connection->createCommand("INSERT INTO upsert_test (username, score) VALUES ('{$username}', {$score})")->execute();
	}

	protected function found(string $username): ?UpsertRecord
	{
		return UpsertRecord::finder()->find('username = ?', [$username]);
	}

	protected function rowCount(): int
	{
		return (int) static::$connection->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
	}
}
