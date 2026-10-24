<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlsrv;

/**
 * Connects the shared cases to the prado_unitest SQL Server database through PradoUnit::setupMssqlConnection().
 */
trait SqlsrvConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMssqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getUnsupportedFeatures(): array
	{
		return ['composite_pk_in'];
	}
}
