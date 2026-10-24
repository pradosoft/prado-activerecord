<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

/**
 * Connects the shared cases to the SQLite file that {@see SqliteTestDatabase} builds
 * from scripts/sqlite/database.sql, through PradoUnit::setupSqliteConnection().
 */
trait SqliteConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqliteConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return SqliteTestDatabase::path();
	}

	protected function getUnsupportedFeatures(): array
	{
		// TSqliteMetaData marks a column as primary key only when PRAGMA table_info reports
		// pk = 1, so the second and later columns of a composite key are not reported.
		return ['composite_pk'];
	}
}
