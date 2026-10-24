<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

/**
 * Builds the SQLite database for the shared cases from scripts/sqlite/database.sql.
 *
 * The file lives in the system temporary directory and is rebuilt once per process,
 * so the tests start from the script's data and the repository holds no binary copy.
 */
final class SqliteTestDatabase
{
	private static ?string $path = null;

	/**
	 * @return string path of the database file, built on the first call
	 */
	public static function path(): string
	{
		if (self::$path === null) {
			$path = sys_get_temp_dir() . '/prado-activerecord-' . md5(__DIR__) . '.sqlite';
			if (is_file($path)) {
				unlink($path);
			}
			$pdo = new \PDO('sqlite:' . $path);
			$pdo->exec(file_get_contents(dirname(__DIR__, 2) . '/scripts/sqlite/database.sql'));
			$pdo = null;
			self::$path = $path;
		}
		return self::$path;
	}
}
