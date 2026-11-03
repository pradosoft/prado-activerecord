<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Firebird;

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

}
