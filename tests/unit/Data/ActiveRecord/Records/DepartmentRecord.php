<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class DepartmentRecord extends TActiveRecord
{
	public $department_id;
	public $name;
	public $description;
	public $active;
	public $sort_key;

	const TABLE = 'departments';

	/**
	 * Firebird, Oracle, and IBM DB2 store the fixture's unquoted identifiers in upper case
	 * and return result columns so; the mapping reaches the lower case properties.
	 */
	public static $COLUMN_MAPPING = [
		'DEPARTMENT_ID' => 'department_id',
		'NAME' => 'name',
		'DESCRIPTION' => 'description',
		'ACTIVE' => 'active',
		'SORT_KEY' => 'sort_key',
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
