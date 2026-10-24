<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Ibm;

/**
 * Connects the shared cases to the IBM DB2 database named by DB2_DATABASE through PradoUnit::setupIbmConnection().
 */
trait IbmConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getUnsupportedFeatures(): array
	{
		// pdo_ibm rejects PDO::PARAM_NULL with "Unknown parameter type".
		return ['null_parameters', 'composite_pk_in'];
	}

}
