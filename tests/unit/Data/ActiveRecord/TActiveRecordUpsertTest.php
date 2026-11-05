<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMssqlScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqlSrvScaffoldInput;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordGateway;
use Prado\Data\ActiveRecord\TActiveRecordManager;

/** A gateway that records the conflict writes it receives. */
class UpsertStubGateway extends TActiveRecordGateway
{
	public array $calls = [];
	public mixed $result = 7;

	public function insertOrIgnore(TActiveRecord $record): mixed
	{
		$this->calls[] = ['insertOrIgnore'];
		return $this->result;
	}

	public function upsert(TActiveRecord $record, ?array $updateData = null, ?array $conflictColumns = null): mixed
	{
		$this->calls[] = ['upsert', $updateData, $conflictColumns];
		return $this->result;
	}
}

/** A gateway whose command is the given object, and which exposes the write values. */
class UpsertCommandGateway extends TActiveRecordGateway
{
	public object $command;

	public function getCommand(TActiveRecord $record)
	{
		return $this->command;
	}

	public function insertValues(TActiveRecord $record): array
	{
		return $this->getInsertValues($record);
	}

	public function updateValues(TActiveRecord $record): array
	{
		return $this->getUpdateValues($record);
	}
}

class UpsertPlainRecord extends TActiveRecord
{
	public static ?TActiveRecordGateway $gateway = null;

	public $id;
	public $username;
	public $email;
	public $related;

	public static $COLUMN_MAPPING = ['login' => 'username'];
	public static $RELATIONS = ['related' => [self::HAS_MANY, UpsertPlainRecord::class]];

	public function getRecordGateway()
	{
		return static::$gateway;
	}

	public function getRecordState(): int
	{
		return $this->_recordState;
	}
}

class UpsertDefaultsRecord extends UpsertPlainRecord
{
	const CONFLICT_COLUMNS = ['email'];
	const UPSERT_UPDATE_DATA = ['username', 'email' => 'default'];
}

/**
 * Covers TActiveRecord::insertOrIgnore() and upsert() against a stub gateway, the
 * gateway's check for the PRADO 4.4 data gateway, the values of a schemaless table,
 * and the case-insensitive column mapping.
 */
class TActiveRecordUpsertTest extends \PHPUnit\Framework\TestCase
{
	private UpsertStubGateway $gateway;

	protected function setUp(): void
	{
		$this->gateway = new UpsertStubGateway(new TActiveRecordManager());
		UpsertPlainRecord::$gateway = $this->gateway;
	}

	protected function tearDown(): void
	{
		UpsertPlainRecord::$gateway = null;
	}

	public function testInsertOrIgnoreReturnsTheGatewayResultAndLoadsTheRecord()
	{
		$record = new UpsertPlainRecord();
		$this->assertSame(7, $record->insertOrIgnore());
		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function testInsertOrIgnoreConflictKeepsTheRecordNew()
	{
		$this->gateway->result = false;
		$record = new UpsertPlainRecord();
		$this->assertFalse($record->insertOrIgnore());
		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState());
	}

	public function testCancelledWritesDoNotReachTheGateway()
	{
		$record = new UpsertPlainRecord();
		$record->OnInsert[] = fn ($sender, $param) => $param->setIsValid(false);
		$this->assertFalse($record->insertOrIgnore());
		$this->assertFalse($record->upsert());
		$this->assertSame([], $this->gateway->calls);
	}

	public function testUpsertWithoutDefaultsPassesTheArguments()
	{
		$record = new UpsertPlainRecord();
		$this->assertSame(7, $record->upsert());
		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
		$record->upsert(['email'], ['username']);
		$this->assertSame([['upsert', null, null], ['upsert', ['email'], ['username']]], $this->gateway->calls);
	}

	public function testUpsertFailureKeepsTheRecordNew()
	{
		$this->gateway->result = false;
		$record = new UpsertPlainRecord();
		$this->assertFalse($record->upsert());
		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState());
	}

	public function testUpsertUsesTheClassDefaults()
	{
		(new UpsertDefaultsRecord())->upsert();
		$this->assertSame([['upsert', ['username', 'email' => 'default'], ['email']]], $this->gateway->calls);
	}

	public function testUpsertMergesUpdateDataAndReplacesConflictColumns()
	{
		(new UpsertDefaultsRecord())->upsert(['email' => 'given', 'id'], ['id']);
		$this->assertSame(
			[['upsert', ['username', 'email' => 'given', 'id'], ['id']]],
			$this->gateway->calls
		);
	}

	public function testGatewayRequiresTheDataGatewayMethods()
	{
		$gateway = new UpsertCommandGateway(new TActiveRecordManager());
		$gateway->command = new \stdClass();
		foreach (['insertOrIgnore' => fn () => $gateway->insertOrIgnore(new UpsertPlainRecord()), 'upsert' => fn () => $gateway->upsert(new UpsertPlainRecord())] as $method => $write) {
			try {
				$write();
				$this->fail("{$method}() did not throw");
			} catch (TActiveRecordException $e) {
				$this->assertSame('ar_method_requires_data_gateway', $e->getErrorCode());
				$this->assertStringContainsString("stdClass::{$method}()", $e->getMessage());
			}
		}
	}

	public function testSchemalessTableUsesTheRecordProperties()
	{
		$key = new class () {
			public function getIsPrimaryKey()
			{
				return true;
			}
		};
		$tableInfo = new class ($key) {
			public function __construct(private object $key)
			{
			}

			public function getColumns()
			{
				return ['id' => $this->key];
			}
		};
		$gateway = new UpsertCommandGateway(new TActiveRecordManager());
		$gateway->command = new class ($tableInfo) {
			public function __construct(private object $tableInfo)
			{
			}

			public function getTableInfo()
			{
				return $this->tableInfo;
			}
		};
		$record = new UpsertPlainRecord(['id' => 5, 'username' => 'ann']);
		$record->related = ['ignored'];

		$this->assertSame(['id' => 5, 'username' => 'ann'], $gateway->insertValues($record));
		$this->assertSame([['username' => 'ann'], ['id' => 5]], $gateway->updateValues($record));
	}

	public function testColumnNamesMatchPropertiesWithoutRegardToCase()
	{
		$record = new UpsertPlainRecord();
		$record->setColumnValue('USERNAME', 'ann');
		$this->assertSame('ann', $record->username);
		$this->assertSame('ann', $record->getColumnValue('UserName'));
		$record->setColumnValue('LOGIN', 'ben');
		$this->assertSame('ben', $record->username);
	}

	public function testMssqlScaffoldInputIsTheSqlSrvScaffoldInput()
	{
		$this->assertInstanceOf(TSqlSrvScaffoldInput::class, new TMssqlScaffoldInput());
	}
}
