<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

/**
 * Builds the SQLite database for the shared cases from scripts/sqlite/database.sql.
 *
 * The file lives in the system temporary directory, is built once per process, and is removed
 * when the process ends, so the tests start from the script's data and the repository holds no
 * binary copy.
 */
final class SqliteTestDatabase
{
	private static ?string $path = null;

	private static ?int $owner = null;

	/**
	 * @return string path of the database file, built on the first call
	 */
	public static function path(): string
	{
		if (self::$path === null) {
			$path = sys_get_temp_dir() . '/prado-activerecord-' . md5(__DIR__) . '-' . getmypid() . '.sqlite';
			if (is_file($path)) {
				unlink($path);
			}
			$pdo = new \PDO('sqlite:' . $path);
			$pdo->exec(file_get_contents(dirname(__DIR__, 2) . '/scripts/sqlite/database.sql'));
			$pdo = null;
			self::$path = $path;
			self::$owner = getmypid();
			register_shutdown_function([self::class, 'remove']);
		}
		return self::$path;
	}

	/**
	 * Deletes the database file. Only the process that built it deletes it.
	 */
	public static function remove(): void
	{
		if (self::$path !== null && self::$owner === getmypid() && is_file(self::$path)) {
			unlink(self::$path);
		}
	}
}
