<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecord;

/**
 * A record whose relation properties are declared, so no database is needed to read them.
 */
class ViewDataRecord extends TActiveRecord
{
	public const TABLE = 'view_data';

	public $id;
	public $name;
	public $active;
	public $sections = [];
	public $owner;

	public static $RELATIONS = [
		'sections' => [self::HAS_MANY, ViewDataSection::class, 'record_id'],
		'owner' => [self::BELONGS_TO, ViewDataOwner::class, 'owner_id'],
	];

	public static $COLUMN_MAPPING = ['record_name' => 'name'];

	protected $internal = 'not a column';
}

class ViewDataSection extends TActiveRecord
{
	public const TABLE = 'view_data_sections';

	public $record_id;
	public $title;
}

class ViewDataOwner extends TActiveRecord
{
	public const TABLE = 'view_data_owners';

	public $id;
	public $name;
}

/**
 * Tests {@see TActiveRecord::getViewData()} and {@see TActiveRecord::setViewData()}.
 */
class TActiveRecordViewDataTest extends \PHPUnit\Framework\TestCase
{
	private function record(): ViewDataRecord
	{
		$record = new ViewDataRecord();
		$record->id = 7;
		$record->name = 'Ada';
		$record->active = true;
		$record->sections = [new ViewDataSection(), new ViewDataSection()];
		$owner = new ViewDataOwner();
		$owner->name = 'Grace';
		$record->owner = $owner;
		return $record;
	}

	public function testGetViewDataListsColumnsThenRelations(): void
	{
		$data = $this->record()->getViewData();

		$this->assertSame(['id', 'name', 'active', 'sections', 'owner'], array_keys($data));
	}

	public function testGetViewDataCarriesCurrentValues(): void
	{
		$record = $this->record();

		$data = $record->getViewData();

		$this->assertSame(7, $data['id']);
		$this->assertSame('Ada', $data['name']);
		$this->assertTrue($data['active']);
		$this->assertCount(2, $data['sections']);
		$this->assertSame($record->owner, $data['owner']);
	}

	public function testGetViewDataOmitsStaticAndNonPublicProperties(): void
	{
		$data = $this->record()->getViewData();

		$this->assertArrayNotHasKey('RELATIONS', $data);
		$this->assertArrayNotHasKey('COLUMN_MAPPING', $data);
		$this->assertArrayNotHasKey('internal', $data);
	}

	public function testGetViewDataOfRecordWithoutRelationsIsItsColumns(): void
	{
		$owner = new ViewDataOwner();
		$owner->id = 1;
		$owner->name = 'Grace';

		$this->assertSame(['id' => 1, 'name' => 'Grace'], $owner->getViewData());
	}

	public function testSetViewDataAssignsColumns(): void
	{
		$record = $this->record();

		$record->setViewData(['id' => 8, 'name' => 'Grace', 'active' => false]);

		$this->assertSame(8, $record->id);
		$this->assertSame('Grace', $record->name);
		$this->assertFalse($record->active);
	}

	public function testSetViewDataLeavesColumnsNotInTheData(): void
	{
		$record = $this->record();

		$record->setViewData(['name' => 'Grace']);

		$this->assertSame(7, $record->id);
		$this->assertSame('Grace', $record->name);
		$this->assertTrue($record->active);
	}

	public function testSetViewDataIgnoresRelationsAndUnknownKeys(): void
	{
		$record = $this->record();
		$owner = $record->owner;
		$sections = $record->sections;

		$record->setViewData(['sections' => 'text from a control', 'owner' => 'Grace', 'caption' => 'x', 'internal' => 'y']);

		$this->assertSame($owner, $record->owner);
		$this->assertSame($sections, $record->sections);
		$this->assertFalse(property_exists($record, 'caption'));
	}

	public function testViewDataMethodsMatchTheFrameworkContract(): void
	{
		$get = new \ReflectionMethod(TActiveRecord::class, 'getViewData');
		$set = new \ReflectionMethod(TActiveRecord::class, 'setViewData');

		$this->assertSame('array', (string) $get->getReturnType());
		$this->assertSame(0, $get->getNumberOfParameters());
		$this->assertSame('void', (string) $set->getReturnType());
		$this->assertSame('array', (string) $set->getParameters()[0]->getType());
		if (interface_exists(\Prado\Web\UI\IViewDataModel::class)) {
			foreach ((new \ReflectionClass(\Prado\Web\UI\IViewDataModel::class))->getMethods() as $method) {
				$this->assertTrue(method_exists(TActiveRecord::class, $method->getName()), $method->getName());
			}
		}
	}
}
