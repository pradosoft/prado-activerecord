<?php

namespace Prado\Test\Unit\Shell\Actions;

use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;
use Prado\IO\TTextWriter;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\PradoUnit;

/**
 * Covers the argument handling of `activerecord/generate` and `activerecord/generate-all`.
 * The output namespace points at a directory that does not exist, so no file is written
 * and no table metadata is read; the generator stops after it has parsed its arguments.
 */
class TActiveRecordActionTest extends \PHPUnit\Framework\TestCase
{
	private const OUTPUT = 'Application.nowhere.UserRecord';

	private TTextWriter $_out;

	private function newAction(): TActiveRecordAction
	{
		$this->_out = new TTextWriter();
		$action = new TActiveRecordAction();
		$action->setWriter(new TShellWriter($this->_out));
		return $action;
	}

	private function assertFlags(TActiveRecordAction $action, bool $soap, bool $overwrite): void
	{
		$this->assertSame($soap, PradoUnit::getProp($action, '_soap'), 'soap flag');
		$this->assertSame($overwrite, PradoUnit::getProp($action, '_overwrite'), 'overwrite flag');
	}

	public function testActionGenerate_flagsDefaultToFalse()
	{
		$action = $this->newAction();
		$this->assertTrue($action->actionGenerate(['generate', 'users', self::OUTPUT]));
		$this->assertFlags($action, false, false);
		$this->assertStringContainsString('must be within directory', $this->_out->flush());
	}

	public function testActionGenerate_readsSoapAndOverwrite()
	{
		$action = $this->newAction();
		$action->actionGenerate(['generate', 'users', self::OUTPUT, 'soap', 'overwrite']);
		$this->assertFlags($action, true, true);
	}

	public function testActionGenerate_acceptsTrueForEitherFlag()
	{
		$action = $this->newAction();
		$action->actionGenerate(['generate', 'users', self::OUTPUT, 'true', 'true']);
		$this->assertFlags($action, true, true);
	}

	public function testActionGenerate_overwriteWithoutSoap()
	{
		$action = $this->newAction();
		$action->actionGenerate(['generate', 'users', self::OUTPUT, 'false', 'overwrite']);
		$this->assertFlags($action, false, true);
	}

	public function testGenerate_passesCommandLineTokensThrough()
	{
		$action = $this->newAction();
		$action->generate('generate users ' . self::OUTPUT . ' false overwrite');
		$this->assertFlags($action, false, true);

		$action = $this->newAction();
		$action->generate('generate users ' . self::OUTPUT . ' soap');
		$this->assertFlags($action, true, false);
	}

	public function testGenerate_printsUsageWithoutEnoughArguments()
	{
		$action = $this->newAction();
		$action->generate('generate users');
		$this->assertStringContainsString('Usage: generate', $this->_out->flush());
	}

	/**
	 * actionGenerateAll() closes the connection after listing the tables, which discards an
	 * in-memory SQLite database, so the table is created again before each call.
	 */
	private function createWidgetsTable(TDbConnection $conn): void
	{
		$conn->setActive(true);
		$conn->createCommand('CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT)')->execute();
	}

	public function testActionGenerateAll_forwardsFlagsPrefixAndSuffixToEachTable()
	{
		$manager = TActiveRecordManager::getInstance();
		$previous = $manager->getDbConnection();
		$conn = new TDbConnection('sqlite::memory:');
		$manager->setDbConnection($conn);
		try {
			$this->createWidgetsTable($conn);
			$action = $this->newAction();
			$this->assertTrue($action->actionGenerateAll(['generate-all', 'Application.nowhere', 'soap', 'overwrite', 'Pre', 'Suffix']));
			$this->assertFlags($action, true, true);
			$this->assertSame('Pre', PradoUnit::getProp($action, '_prefix'));
			$this->assertSame('Suffix', PradoUnit::getProp($action, '_postfix'));
			$this->assertStringContainsString('PreWidgetsSuffix', $this->_out->flush());

			$this->createWidgetsTable($conn);
			$action = $this->newAction();
			$action->actionGenerateAll(['generate-all', 'Application.nowhere', 'false', 'overwrite']);
			$this->assertFlags($action, false, true);
			$this->assertStringContainsString('Widgets.php', $this->_out->flush());
		} finally {
			$manager->setDbConnection($previous);
		}
	}
}
