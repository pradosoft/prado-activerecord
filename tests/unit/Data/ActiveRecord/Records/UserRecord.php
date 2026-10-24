<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\TPropertyValue;

class UserRecord extends TActiveRecord
{
	public $username;
	public $password;
	public $email;
	public $first_name;
	public $last_name;
	public $job_title;
	public $work_phone;
	public $work_fax;
	public $active = true;
	public $department_id;
	public $salutation;
	public $hint_question;
	public $hint_answer;

	private $_level = -1;

	const TABLE = 'users';

	/**
	 * Firebird, Oracle, and IBM DB2 store the fixture's unquoted identifiers in upper case
	 * and return result columns so; the mapping reaches the lower case properties.
	 */
	public static $COLUMN_MAPPING = [
		'USERNAME' => 'username',
		'PASSWORD' => 'password',
		'EMAIL' => 'email',
		'FIRST_NAME' => 'first_name',
		'LAST_NAME' => 'last_name',
		'JOB_TITLE' => 'job_title',
		'WORK_PHONE' => 'work_phone',
		'WORK_FAX' => 'work_fax',
		'ACTIVE' => 'active',
		'DEPARTMENT_ID' => 'department_id',
		'SALUTATION' => 'salutation',
		'HINT_QUESTION' => 'hint_question',
		'HINT_ANSWER' => 'hint_answer',
	];

	public function getLevel()
	{
		return $this->_level;
	}

	public function setLevel($level)
	{
		$this->_level = TPropertyValue::ensureInteger($level);
	}

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
