<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class SimpleUser extends TActiveRecord
{
	public $username;

	const TABLE = 'simple_users';

	/**
	 * Firebird, Oracle, and IBM DB2 store the fixture's unquoted identifiers in upper case
	 * and return result columns so; the mapping reaches the lower case properties.
	 */
	public static $COLUMN_MAPPING = [
		'USERNAME' => 'username',
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
