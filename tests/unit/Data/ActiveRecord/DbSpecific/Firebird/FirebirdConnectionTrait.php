<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Firebird;

use Prado\Data\TDbColumnCaseMode;
use Prado\Data\TDbConnection;

/**
 * Connects the shared cases to the Firebird database named by FIREBIRD_DB_PATH through PradoUnit::setupFirebirdConnection().
 */
trait FirebirdConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getUnsupportedFeatures(): array
	{
		return ['composite_pk_in'];
	}

	protected function configureConnection(TDbConnection $connection): void
	{
		// Firebird stores unquoted identifiers in upper case and returns them so;
		// the fixture records declare lower case properties.
		$connection->setColumnCase(TDbColumnCaseMode::LowerCase);
	}
}
