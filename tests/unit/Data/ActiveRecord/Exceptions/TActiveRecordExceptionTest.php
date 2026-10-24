<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Exceptions;

use Prado\Data\ActiveRecord\Exceptions\TActiveRecordConfigurationException;
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;

class TActiveRecordExceptionTest extends \PHPUnit\Framework\TestCase
{
	private const SRC = __DIR__ . '/../../../../../src/Data/ActiveRecord';
	private const MESSAGES = self::SRC . '/Exceptions/messages.txt';

	/**
	 * @return string[] the `ar_*` and `scaffold_*` codes defined in messages.txt
	 */
	private function definedCodes(): array
	{
		preg_match_all('/^((?:ar|scaffold)_[a-z_]+)\s*=/m', file_get_contents(self::MESSAGES), $m);
		return $m[1];
	}

	/**
	 * @return string[] the `ar_*` and `scaffold_*` codes passed to an exception constructor anywhere under src/Data/ActiveRecord
	 */
	private function thrownCodes(): array
	{
		$codes = [];
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::SRC));
		foreach ($files as $file) {
			if ($file->getExtension() === 'php') {
				preg_match_all("/new \\\\?[A-Za-z\\\\]*Exception\\(\\s*'((?:ar|scaffold)_[a-z_]+)'/", file_get_contents($file->getPathname()), $m);
				$codes = array_merge($codes, $m[1]);
			}
		}
		return array_values(array_unique($codes));
	}

	public function testEveryCodeThrownInSourceIsDefinedInMessagesFile()
	{
		$missing = array_diff($this->thrownCodes(), $this->definedCodes());
		$this->assertSame([], array_values($missing), 'Codes thrown without message text: ' . implode(', ', $missing));
	}

	public function testMessageIsTranslatedWithParameters()
	{
		$e = new TActiveRecordException('ar_save_invalid', 'App\\UserRecord');
		$this->assertSame('ar_save_invalid', $e->getErrorCode());
		$this->assertStringContainsString("'App\\UserRecord'", $e->getMessage());
		$this->assertStringNotContainsString('ar_save_invalid', $e->getMessage());
	}

	public function testConfigurationExceptionUsesTheSameMessageFile()
	{
		$e = new TActiveRecordConfigurationException('ar_invalid_table', 'missing_table');
		$this->assertStringContainsString("'missing_table'", $e->getMessage());
		$this->assertStringNotContainsString('ar_invalid_table', $e->getMessage());
	}
}
