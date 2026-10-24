<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Oracle;

use Prado\Data\TDbColumnCaseMode;
use Prado\Data\TDbConnection;

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
		return ['identity'];
	}

	protected function configureConnection(TDbConnection $connection): void
	{
		$connection->setColumnCase(TDbColumnCaseMode::LowerCase);
	}
}
