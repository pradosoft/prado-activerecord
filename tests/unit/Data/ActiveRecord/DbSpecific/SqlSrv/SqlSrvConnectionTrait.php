<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\SqlSrv;

/**
 * Connects the shared cases to the prado_unitest SQL Server database through PradoUnit::setupSqlSrvConnection().
 */
trait SqlSrvConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqlSrvConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getUnsupportedFeatures(): array
	{
		// PRADO 4.4 raises no error for a positional parameter past the statement's placeholders.
		return ['composite_pk_in', 'merge_transaction', 'extra_parameter_error'];
	}
}
