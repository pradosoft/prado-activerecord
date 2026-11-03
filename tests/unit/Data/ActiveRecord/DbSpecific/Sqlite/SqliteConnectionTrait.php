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
}
