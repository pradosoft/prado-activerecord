<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\Relations\TActiveRecordHasManyAssociation;
use Prado\Data\ActiveRecord\Relations\TActiveRecordRelationContext;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Data\ActiveRecord\TActiveRecordGateway;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\Common\IDataSchemalessTableInfo;
use Prado\Data\Common\IDataTableInfo;
use Prado\Data\IDataConnection;
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

/** A gateway that hands out the association table's metadata through a wrapper. */
class DkSchemalessGateway extends TActiveRecordGateway
{
	public ?\Closure $wrap = null;

	public function getTableInfo(IDataConnection $connection, $tableName)
	{
		$info = parent::getTableInfo($connection, $tableName);
		return $tableName === 'dk_post_tag' && $this->wrap !== null ? ($this->wrap)($info) : $info;
	}
}

/** Posts whose association table, dk_post_tag, is reported schemaless by {@see DkSchemalessGateway}. */
class DkSchemalessPost extends DkPost
{
	public static ?DkSchemalessGateway $gateway = null;
	public $undeclaredTags = [];

	public static $RELATIONS = [
		'tags' => [self::MANY_TO_MANY, DkTag::class, 'dk_post_tag.(post_id, tag_id)'],
		'undeclaredTags' => [self::MANY_TO_MANY, DkTag::class, 'dk_post_tag'],
	];

	public function getRecordGateway()
	{
		return static::$gateway;
	}

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
		foreach ([1 => [1, 2], 2 => [], 3 => [2]] as $id => $tagIds) {
			$post = DkPost::finder()->findByPk($id);
			$handler = new DkQueriesAssociation(new TActiveRecordRelationContext($post, 'tags', DkPost::$RELATIONS['tags']), new TActiveRecordCriteria());
			$handler->fetchResultsInto($post);
			$this->assertSame($tagIds, self::ids($post->tags), "post {$id}");
		}
	}

	/**
	 * Reports dk_post_tag as a schemaless table without foreign keys, as a document
	 * store's collection would be.
	 */
	private function useSchemalessAssociation(): void
	{
		$gateway = new DkSchemalessGateway(TActiveRecordManager::getInstance());
		$gateway->wrap = fn (IDataTableInfo $info) => new class ($info) implements IDataTableInfo, IDataSchemalessTableInfo {
			public function __construct(private IDataTableInfo $info)
			{
			}

			public function isSchemaless(): bool
			{
				return true;
			}

			public function getTableName()
			{
				return $this->info->getTableName();
			}

			public function getTableFullName()
			{
				return $this->info->getTableFullName();
			}

			public function getIsView()
			{
				return false;
			}

			public function getColumns()
			{
				return $this->info->getColumns();
			}

			public function getColumn($name)
			{
				return $this->info->getColumn($name);
			}

			public function getColumnNames()
			{
				return $this->info->getColumnNames();
			}

			public function getLowerCaseColumnNames()
			{
				return $this->info->getLowerCaseColumnNames();
			}

			public function getPrimaryKeys()
			{
				return [];
			}

			public function getForeignKeys()
			{
				return [];
			}

			public function createCommandBuilder($connection)
			{
				return $this->info->createCommandBuilder($connection);
			}
		};
		DkSchemalessPost::$gateway = $gateway;
	}

	public function testManyToManyThroughSchemalessAssociation()
	{
		$this->useSchemalessAssociation();
		$finder = DkSchemalessPost::finder();
		$sql = [];
		$handler = function ($sender, $param) use (&$sql) {
			$sql[] = $param->getCommand()->getText();
		};
		$finder->OnCreateCommand[] = $handler;
		try {
			$posts = $finder->withTags()->findAll();
		} finally {
			$finder->OnCreateCommand->remove($handler);
		}
		$tags = [];
		foreach ($posts as $post) {
			$tags[(int) $post->id] = self::ids($post->tags);
		}
		ksort($tags);
		$this->assertSame([1 => [1, 2], 2 => [], 3 => [2]], $tags);
		$this->assertStringContainsString('dk_tag', implode(' ', $sql), 'the foreign query runs');
		$this->assertStringNotContainsStringIgnoringCase('JOIN', implode(' ', $sql), 'a schemaless association is not joined');
	}

	public function testSchemalessAssociationRequiresBothColumns()
	{
		$this->useSchemalessAssociation();
		try {
			DkSchemalessPost::finder()->withUndeclaredTags()->findByPk(1);
			$this->fail('The relation loaded without naming its association columns.');
		} catch (TActiveRecordException $e) {
			$this->assertSame('ar_mn_relation_keys_required', $e->getErrorCode());
			$this->assertStringContainsString("'dk_post_tag.(sourceKey, foreignKey)'", $e->getMessage());
		}
	}
}
