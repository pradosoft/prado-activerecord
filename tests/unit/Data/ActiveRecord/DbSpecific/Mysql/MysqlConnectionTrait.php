<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Mysql;

/**
 * Connects the shared cases to the prado_unitest MySQL database through PradoUnit::setupMysqlConnection().
 */
trait MysqlConnectionTrait
{
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMysqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}
}
