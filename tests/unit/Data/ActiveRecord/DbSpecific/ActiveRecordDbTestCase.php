<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific;

use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;
use Prado\Test\Unit\Data\ActiveRecord\Records\Blogs;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepSections;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;
use Prado\Test\Unit\Data\ActiveRecord\Records\SimpleUser;
use Prado\Test\Unit\Data\ActiveRecord\Records\UserRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitActiveRecordConnectionTrait;

/**
 * Base class of the database test cases in Cases/.
 *
 * Each driver directory pairs every case with that driver's connection trait. The
 * trait names the PradoUnit::setup*Connection() helper, the database, the connection
 * settings, and the features the driver lacks. The first test of a class opens the
 * connection through PradoUnitActiveRecordConnectionTrait, which hands it to the
 * TActiveRecordManager and fails that test, then skips the rest, when the server or a
 * table is missing. The connection is shared by the tests of the class and closed in
 * tearDownAfterClass(). setUp() also binds the cached finder of every fixture record
 * to it: TActiveRecord::finder() keeps one instance per class for the whole process,
 * and that instance keeps the first connection it saw, so without the rebinding a
 * process that runs two driver suites would query the first driver from the second.
 */
abstract class ActiveRecordDbTestCase extends \PHPUnit\Framework\TestCase
{
	use PradoUnitActiveRecordConnectionTrait;

	/**
	 * @var ?TDbConnection the connection shared by the tests of one class. A static
	 * property is shared by every subclass, so setUpBeforeClass() resets it.
	 */
	protected static ?TDbConnection $connection = null;

	protected function getTestTables(): array
	{
		return [DepartmentRecord::TABLE];
	}

	/**
	 * @return string[] record classes whose finder is bound to the test connection
	 */
	protected function getRecordClasses(): array
	{
		return [Blogs::class, DepSections::class, DepartmentRecord::class, SimpleUser::class, UserRecord::class];
	}

	/**
	 * Features the driver lacks. A test that needs one calls {@see requireFeature()}.
	 * - `composite_pk`: a composite primary key reported in full by the driver metadata
	 * - `composite_pk_in`: the `(a, b) IN ((1, 2), (3, 4))` form the gateway builds for a composite primary key
	 * - `identity`: an auto-increment primary key that the metadata reports and the gateway reads back after an insert
	 * - `null_parameters`: a NULL bound as PDO::PARAM_NULL
	 * @return string[]
	 */
	protected function getUnsupportedFeatures(): array
	{
		return [];
	}

	/**
	 * Driver settings applied to the connection once it is open.
	 * @param TDbConnection $connection the open test connection
	 */
	protected function configureConnection(TDbConnection $connection): void
	{
	}

	public static function setUpBeforeClass(): void
	{
		static::closeConnection();
	}

	public static function tearDownAfterClass(): void
	{
		static::closeConnection();
	}

	protected static function closeConnection(): void
	{
		if (static::$connection !== null) {
			static::$connection->setActive(false);
			static::$connection = null;
		}
	}

	protected function setUp(): void
	{
		if (static::$connection === null) {
			$connection = $this->setUpConnection();
			if ($connection === null) {
				return;
			}
			$this->configureConnection($connection);
			static::$connection = $connection;
		} else {
			TActiveRecordManager::getInstance()->setDbConnection(static::$connection);
		}
		foreach ($this->getRecordClasses() as $class) {
			$class::finder()->setDbConnection(static::$connection);
		}
	}

	/**
	 * Skips the test when the driver lacks the feature.
	 * @param string $feature a key of {@see getUnsupportedFeatures()}
	 * @param string $reason the skip message
	 */
	protected function requireFeature(string $feature, string $reason): void
	{
		if (in_array($feature, $this->getUnsupportedFeatures(), true)) {
			$this->markTestSkipped($reason);
		}
	}
}
