<?php

/**
 * PradoUnitActiveRecordConnectionTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado-activerecord
 * @license https://github.com/pradosoft/prado-activerecord/blob/main/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;

/**
 * PradoUnitActiveRecordConnectionTrait
 *
 * {@see PradoUnitDataConnectionTrait} with one more step: the connection it opens
 * becomes the `DbConnection` of {@see TActiveRecordManager::getInstance()}, so the
 * records under test reach the database without a `getDbConnection()` of their own.
 *
 * The trait composes the framework trait and wraps its `setUpConnection()`. The
 * framework harness knows nothing about Active Record; this trait is the package's
 * hook into it, and it works with any PRADO version that ships the framework trait.
 * A test class uses this trait in place of `PradoUnitDataConnectionTrait` and
 * overrides `getDatabaseName()`, `getPradoUnitSetup()`, and `getTestTables()` as
 * before.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 1.0.0 of prado-activerecord
 */
trait PradoUnitActiveRecordConnectionTrait
{
	use PradoUnitDataConnectionTrait {
		setUpConnection as protected setUpDataConnection;
	}

	/**
	 * Opens the connection through {@see PradoUnitDataConnectionTrait::setUpConnection()}
	 * and sets it on the shared Active Record manager.
	 * @return ?TDbConnection the connection; null when the test is failed or skipped
	 */
	protected function setUpConnection(): ?TDbConnection
	{
		$conn = $this->setUpDataConnection();
		if ($conn !== null) {
			TActiveRecordManager::getInstance()->setDbConnection($conn);
		}
		return $conn;
	}
}
