<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Pgsql;

/**
 * Connects the shared cases to the prado_unitest PostgreSQL database through PradoUnit::setupPgsqlConnection().
 */
trait PgsqlConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupPgsqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}
}
