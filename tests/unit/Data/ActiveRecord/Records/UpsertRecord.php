<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

/**
 * The upsert_test table: an identity primary key, a unique username, a score, and a note.
 * Conflicts target the username.
 */
class UpsertRecord extends TActiveRecord
{
	const TABLE = 'upsert_test';

	const CONFLICT_COLUMNS = ['username'];

	public $upsert_id;
	public $username;
	public $score = 0;
	public $note;

	/**
	 * @return int the record state, one of TActiveRecord::STATE_NEW, STATE_LOADED, or STATE_DELETED.
	 */
	public function getRecordState(): int
	{
		return $this->_recordState;
	}

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
