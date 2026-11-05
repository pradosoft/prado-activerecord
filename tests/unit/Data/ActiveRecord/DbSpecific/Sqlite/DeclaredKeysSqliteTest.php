<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\Relations\TActiveRecordHasManyAssociation;
use Prado\Data\ActiveRecord\Relations\TActiveRecordRelationContext;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\TDbConnection;

/**
 * Records over an in-memory SQLite database whose tables declare no foreign keys,
 * as a store without foreign key metadata does. The relations name their key columns.
 */
abstract class DeclaredKeysSqliteRecord extends TActiveRecord
{
	public const SCHEMA = <<<'SQL'
		CREATE TABLE dk_author (id integer PRIMARY KEY, name varchar(20));
		CREATE TABLE dk_post (id integer PRIMARY KEY, author_id integer, title varchar(20));
		CREATE TABLE dk_tag (id integer PRIMARY KEY, name varchar(20));
		CREATE TABLE dk_post_tag (post_id integer NOT NULL, tag_id integer NOT NULL);
		INSERT INTO dk_author VALUES (1, 'ann');
		INSERT INTO dk_author VALUES (2, 'ben');
		INSERT INTO dk_post VALUES (1, 1, 'first');
		INSERT INTO dk_post VALUES (2, 1, 'second');
		INSERT INTO dk_post VALUES (3, 2, 'third');
		INSERT INTO dk_tag VALUES (1, 'php');
		INSERT INTO dk_tag VALUES (2, 'sql');
		INSERT INTO dk_post_tag VALUES (1, 1);
		INSERT INTO dk_post_tag VALUES (1, 2);
		INSERT INTO dk_post_tag VALUES (3, 2);
		SQL;

	protected static $conn;

	public static function resetDatabase()
	{
		self::$conn = null;
		self::connection()->getPdoInstance()->exec(self::SCHEMA);
	}

	public static function connection()
	{
		if (self::$conn === null) {
			self::$conn = new TDbConnection('sqlite::memory:');
			self::$conn->setActive(true);
		}
		return self::$conn;
	}

	public function getDbConnection()
	{
		return self::connection();
	}
}

class DkAuthor extends DeclaredKeysSqliteRecord
{
	public const TABLE = 'dk_author';

	public $id;
	public $name;
	public $posts = [];

	public static $RELATIONS = [
		'posts' => [self::HAS_MANY, DkPost::class, 'author_id'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class DkPost extends DeclaredKeysSqliteRecord
{
	public const TABLE = 'dk_post';

	public $id;
	public $author_id;
	public $title;
	public $author;
	public $tags = [];
	public $undeclared;

	public static $RELATIONS = [
		'author' => [self::BELONGS_TO, DkAuthor::class, 'author_id'],
		'tags' => [self::MANY_TO_MANY, DkTag::class, 'dk_post_tag.(post_id, tag_id)'],
		'undeclared' => [self::BELONGS_TO, DkAuthor::class],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class DkTag extends DeclaredKeysSqliteRecord
{
	public const TABLE = 'dk_tag';

	public $id;
	public $name;

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

/** Loads MANY_TO_MANY records with the two queries a command builder without joins uses. */
class DkQueriesAssociation extends TActiveRecordHasManyAssociation
{
	protected function fetchForeignObjects(&$results, $foreignKeys, $indexValues, $sourceKeys)
	{
		$this->fetchForeignObjectsByQueries($results, $foreignKeys, $indexValues, $sourceKeys);
	}
}

/**
 * Covers the relations of tables without foreign key metadata: the key columns a
 * relation names map to the primary key of the related table.
 */
class DeclaredKeysSqliteTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		DeclaredKeysSqliteRecord::resetDatabase();
	}

	private static function ids(array $records): array
	{
		$ids = array_map(fn ($record) => (int) $record->id, $records);
		sort($ids);
		return $ids;
	}

	public function testBelongsToUsesTheNamedColumn()
	{
		$post = DkPost::finder()->withAuthor()->findByPk(3);
		$this->assertSame('ben', $post->author->name);
	}

	public function testHasManyUsesTheNamedColumn()
	{
		$author = DkAuthor::finder()->withPosts()->findByPk(1);
		$this->assertSame([1, 2], self::ids($author->posts));
	}

	public function testManyToManyUsesBothNamedColumns()
	{
		$post = DkPost::finder()->withTags()->findByPk(1);
		$this->assertSame([1, 2], self::ids($post->tags));

		$post = DkPost::finder()->withTags()->findByPk(2);
		$this->assertSame([], $post->tags);
	}

	public function testRelationWithoutNamedColumnHasNoKeys()
	{
		$this->expectException(TActiveRecordException::class);
		DkPost::finder()->withUndeclared()->findByPk(1);
	}

	public function testManyToManyByQueries()
	{
		if (!method_exists(TDbCommandBuilder::class, 'createIndexedFindCommand')) {
			$this->markTestSkipped('The command builder has no createIndexedFindCommand() before PRADO 4.4.');
		}
		foreach ([1 => [1, 2], 2 => [], 3 => [2]] as $id => $tagIds) {
			$post = DkPost::finder()->findByPk($id);
			$handler = new DkQueriesAssociation(new TActiveRecordRelationContext($post, 'tags', DkPost::$RELATIONS['tags']), new TActiveRecordCriteria());
			$handler->fetchResultsInto($post);
			$this->assertSame($tagIds, self::ids($post->tags), "post {$id}");
		}
	}
}
