<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TConfigurationException;

/**
 * Unit tests for TScaffoldInputBase.
 *
 * Tests the createInputBuilder factory method and fxActiveRecordScaffoldInputClass event.
 */
class TScaffoldInputBaseTest extends \PHPUnit\Framework\TestCase
{
	private function createMockRecord(string $driver): TActiveRecord
	{
		$conn = $this->createMock(\Prado\Data\TDbConnection::class);
		$conn->method('getDriverName')->willReturn($driver);

		$record = $this->createMock(TActiveRecord::class);
		$record->method('getDbConnection')->willReturn($conn);
		return $record;
	}

	public function test_createInputBuilder_throws_for_unknown_driver_with_no_event_handlers()
	{
		$record = $this->createMockRecord('unknown_driver');
		$conn = $record->getDbConnection();
		$conn->expects($this->once())
			->method('raiseEvent')
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_raises_fxActiveRecordScaffoldInputClass_for_unknown_driver()
	{
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();

		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordScaffoldInputClass', $this->anything(), 'custom_driver')
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_calls_setActive_on_connection()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLITE);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('setActive')->with(true);

		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_valid_mysql_driver()
	{
		$record = $this->createMockRecord(TDbDriver::EXTENSION_MYSQLI);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_mysql_old_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_MYSQL);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlite_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLITE);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlite2_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLITE2);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_pgsql_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_PGSQL);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_mssql_driver()
	{
		$record = $this->createMockRecord(TDbDriver::EXTENSION_MSSQL);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMssqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_ibm_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_IBM);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TIbmScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_firebird_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_FIREBIRD);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_interbase_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_INTERBASE);
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_driver_name_is_case_insensitive()
	{
		$record = $this->createMockRecord('PGSQL');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlsrv_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLSRV);
		$record->getDbConnection()->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMssqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_dblib_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_DBLIB);
		$record->getDbConnection()->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMssqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_uses_first_class_from_fxActiveRecordScaffoldInputClass()
	{
		$record = $this->createMockRecord('custom_driver');
		$record->getDbConnection()->method('raiseEvent')
			->willReturn([TPgsqlScaffoldInput::class, TMysqlScaffoldInput::class]);

		$this->assertInstanceOf(TPgsqlScaffoldInput::class, TScaffoldInputBase::createInputBuilder($record));
	}

	public function test_createInputBuilder_rejects_class_not_implementing_IScaffoldInput()
	{
		$record = $this->createMockRecord('custom_driver');
		$record->getDbConnection()->method('raiseEvent')->willReturn([\stdClass::class]);

		try {
			TScaffoldInputBase::createInputBuilder($record);
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('ar_not_input_base', $e->getErrorCode());
			$this->assertStringContainsString('stdClass', $e->getMessage());
			$this->assertStringContainsString(IScaffoldInput::class, $e->getMessage());
		}
	}

	public static function nonStringResultProvider(): array
	{
		return [
			'object' => [new \stdClass(), 'stdClass'],
			'array' => [['x'], 'array'],
			'integer' => [42, 'int'],
		];
	}

	/**
	 * @dataProvider nonStringResultProvider
	 * @param mixed $result
	 * @param string $type
	 */
	public function test_createInputBuilder_rejects_non_string_handler_result($result, string $type)
	{
		$record = $this->createMockRecord('custom_driver');
		$record->getDbConnection()->method('raiseEvent')->willReturn([$result]);

		try {
			TScaffoldInputBase::createInputBuilder($record);
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('ar_not_input_base', $e->getErrorCode());
			$this->assertStringContainsString("class {$type}", $e->getMessage());
		}
	}

	public function test_createInputBuilder_throws_for_unknown_driver_on_plain_connection()
	{
		$connection = new class () {
			public bool $active = false;

			public function setActive($value)
			{
				$this->active = $value;
			}

			public function getDriverName()
			{
				return 'plain_driver';
			}
		};
		$record = $this->createMock(TActiveRecord::class);
		$record->method('getDbConnection')->willReturn($connection);

		try {
			TScaffoldInputBase::createInputBuilder($record);
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('ar_invalid_database_driver', $e->getErrorCode());
			$this->assertStringContainsString('plain_driver', $e->getMessage());
		}
		$this->assertTrue($connection->active);
	}

	public function test_base_builder_creates_no_input_and_reads_null()
	{
		$builder = new TScaffoldInputBase();
		$item = new \Prado\Web\UI\WebControls\TRepeaterItem();
		$input = new \Prado\Web\UI\WebControls\TPlaceHolder();
		$label = new \Prado\Web\UI\WebControls\TLabel();
		$item->getControls()->add($input);
		$item->getControls()->add($label);
		$item->registerObject('_input', $input);
		$item->registerObject('_label', $label);
		$column = new \Prado\Data\Common\TDbTableColumn(['ColumnId' => 'username', 'DbType' => 'varchar']);
		$parent = new ScaffoldInputParentStub();
		$record = new \Prado\Test\Unit\Data\ActiveRecord\Records\SimpleUser();
		$record->username = 'old';

		$builder->createScaffoldInput($parent, $item, $column, $record);
		$this->assertSame('username', $item->getCustomData());
		$this->assertCount(0, $input->getControls());
		$this->assertSame('', $label->getText());

		$builder->loadScaffoldInput($parent, $item, $column, $record);
		$this->assertNull($record->username);
	}

	public function test_default_id_constant()
	{
		$this->assertEquals('scaffold_input', TScaffoldInputBase::DEFAULT_ID);
	}
}
