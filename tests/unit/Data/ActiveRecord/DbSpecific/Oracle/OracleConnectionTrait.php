<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Oracle;

/**
 * Connects the shared cases to the Oracle service named by ORACLE_SERVICE_NAME through PradoUnit::setupOciConnection().
 */
trait OracleConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupOciConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getUnsupportedFeatures(): array
	{
		// TOracleMetaData reports no column as auto-increment (attisserial is always 0),
		// so the gateway cannot read an identity value back after an insert.
		// TOracleDbCommand binds only the parameters its statement names, so an extra one is ignored.
		return ['identity', 'merge_transaction', 'extra_parameter_error'];
	}

}
