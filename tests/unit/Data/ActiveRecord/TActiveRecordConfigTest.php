<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\IModuleDependency;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Test\Unit\Harness\Traits\PradoUnitModuleDependencyTrait;
use Prado\Test\Unit\Harness\TTestShellApplication;

class TActiveRecordConfigTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;

	/**
	 * IModuleDependency contract for TActiveRecordConfig.
	 *
	 * TActiveRecordConfig::init() eagerly materializes the database connection via
	 * $manager->setDbConnection($this->getDbConnection()). When ConnectionID
	 * forwards to another TDataSourceConfig, that upstream's init() must have
	 * applied its <database> element first, so the upstream ID has been declared
	 * a hard init() dependency.
	 */
	public function testImplementsIModuleDependency()
	{
		$this->assertInstanceOf(IModuleDependency::class, new TActiveRecordConfig());
	}

	public function testGetModuleDependencies_noConnectionID_returnsNoDeps()
	{
		$config = new TActiveRecordConfig();
		$this->assertModuleDependency(null, $config->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_connectionIDSet_returnsIt()
	{
		$config = new TActiveRecordConfig();
		$config->setConnectionID('db');
		$this->assertModuleDependency('db', $config->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_returnsSameRegardlessOfIsPreInit()
	{
		$config = new TActiveRecordConfig();
		$config->setConnectionID('db');
		$this->assertModuleDependency(
			$config->getModuleDependencies(true),
			$config->getModuleDependencies(false)
		);
	}

	/**
	 * init() registers TActiveRecordAction with a TShellApplication, and a second init()
	 * keeps the action that is already registered.
	 */
	public function testInit_registersShellActionOnShellApplication()
	{
		$manager = TActiveRecordManager::getInstance();
		$app = new TTestShellApplication();
		try {
			$config = new TActiveRecordConfig();
			$app->setModule('activerecord', $config);
			$this->assertFalse($app->hasShellActionClass(TActiveRecordAction::class));

			$config->init(null);

			$this->assertTrue($app->hasShellActionClass(TActiveRecordAction::class));
			$action = $app->getShellActions()[TActiveRecordAction::class];
			$this->assertInstanceOf(TActiveRecordAction::class, $action);

			$config->init(null);

			$this->assertSame($action, $app->getShellActions()[TActiveRecordAction::class]);
		} finally {
			$app->restoreApplication();
			TActiveRecordManager::getInstance($manager);
		}
	}
}
