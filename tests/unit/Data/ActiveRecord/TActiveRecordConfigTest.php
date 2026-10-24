<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Caching\TCache;
use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Data\ActiveRecord\TActiveRecordGateway;
use Prado\Data\ActiveRecord\TActiveRecordInvalidFinderResult;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\IModuleDependency;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Test\Unit\Harness\Traits\PradoUnitModuleDependencyTrait;
use Prado\Test\Unit\Harness\TTestShellApplication;

class ConfigTestManager extends TActiveRecordManager
{
}

class ConfigTestGateway extends TActiveRecordGateway
{
}

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

	public function testProperties_defaultsAndSetters()
	{
		$config = new TActiveRecordConfig();
		$this->assertSame(TActiveRecordConfig::DEFAULT_MANAGER_CLASS, $config->getManagerClass());
		$this->assertSame(TActiveRecordConfig::DEFAULT_GATEWAY_CLASS, $config->getGatewayClass());
		$this->assertFalse($config->getEnableCache());
		$this->assertSame(TActiveRecordInvalidFinderResult::Null, $config->getInvalidFinderResult());

		$config->setManagerClass(ConfigTestManager::class);
		$config->setGatewayClass(ConfigTestGateway::class);
		$config->setEnableCache('true');
		$config->setInvalidFinderResult('Exception');

		$this->assertSame(ConfigTestManager::class, $config->getManagerClass());
		$this->assertSame(ConfigTestGateway::class, $config->getGatewayClass());
		$this->assertTrue($config->getEnableCache());
		$this->assertSame(TActiveRecordInvalidFinderResult::Exception, $config->getInvalidFinderResult());
	}

	public function testSetInvalidFinderResult_rejectsUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TActiveRecordConfig())->setInvalidFinderResult('Ignore');
	}

	/**
	 * init() creates the manager from ManagerClass, makes it the singleton, and hands it
	 * the connection, the application cache (EnableCache), the gateway class, and the
	 * InvalidFinderResult setting.
	 */
	public function testInit_configuresManager()
	{
		$previous = TActiveRecordManager::getInstance();
		$app = Prado::getApplication();
		$appCache = $app->getCache();
		$cache = $this->createMock(TCache::class);
		$app->setCache($cache);
		try {
			$config = new TActiveRecordConfig();
			$config->setManagerClass(ConfigTestManager::class);
			$config->setGatewayClass(ConfigTestGateway::class);
			$config->setEnableCache(true);
			$config->setInvalidFinderResult(TActiveRecordInvalidFinderResult::Exception);
			$config->getDbConnection()->setConnectionString('sqlite::memory:');

			$config->init(null);

			$manager = TActiveRecordManager::getInstance();
			$this->assertInstanceOf(ConfigTestManager::class, $manager);
			$this->assertSame($manager, $config->getManager());
			$this->assertSame($cache, $manager->getCache());
			$this->assertSame($config->getDbConnection(), $manager->getDbConnection());
			$this->assertInstanceOf(ConfigTestGateway::class, $manager->getRecordGateway());
			$this->assertSame(TActiveRecordInvalidFinderResult::Exception, $manager->getInvalidFinderResult());
		} finally {
			PradoUnit::setProp($app, '_cache', $appCache);
			TActiveRecordManager::getInstance($previous);
		}
	}

	public function testInit_withoutEnableCache_leavesManagerUncached()
	{
		$previous = TActiveRecordManager::getInstance();
		$app = Prado::getApplication();
		$appCache = $app->getCache();
		$app->setCache($this->createMock(TCache::class));
		try {
			$config = new TActiveRecordConfig();
			$config->init(null);
			$this->assertNull($config->getManager()->getCache());
		} finally {
			PradoUnit::setProp($app, '_cache', $appCache);
			TActiveRecordManager::getInstance($previous);
		}
	}
}
