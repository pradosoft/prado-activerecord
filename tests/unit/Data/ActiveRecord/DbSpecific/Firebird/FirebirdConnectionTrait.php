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
		// TFirebirdCommandBuilder::getLastInsertID() reads RDB$GET_CONTEXT('SYSTEM', 'LAST_INSERT_ID'),
		// a context variable Firebird does not define, so the gateway cannot read an identity value back.
		return ['composite_pk_in', 'identity'];
	}

}
