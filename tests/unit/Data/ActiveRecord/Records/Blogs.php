<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Records;

use Prado\Data\ActiveRecord\TActiveRecord;

class Blogs extends TActiveRecord
{
	const TABLE = 'blogs';

	/**
	 * Firebird, Oracle, and IBM DB2 store the fixture's unquoted identifiers in upper case
	 * and return result columns so; the mapping reaches the lower case properties.
	 */
	public static $COLUMN_MAPPING = [
		'BLOG_ID' => 'blog_id',
		'BLOG_NAME' => 'blog_name',
		'BLOG_AUTHOR' => 'blog_author',
	];

	public $blog_id;
	public $blog_name;
	public $blog_author;

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
