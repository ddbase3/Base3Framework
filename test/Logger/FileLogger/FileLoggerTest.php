<?php declare(strict_types=1);

namespace Base3\Test\Logger\FileLogger;

use PHPUnit\Framework\TestCase;
use Base3\Logger\FileLogger\FileLogger;

/**
 * Define DIR_LOCAL for FileLogger constructor usage inside tests.
 * FileLogger reads it as an unqualified constant inside its namespace.
 */
if (!defined('Base3\\Logger\\FileLogger\\DIR_LOCAL')) {
	define('Base3\\Logger\\FileLogger\\DIR_LOCAL', '');
}

/**
 * @covers \Base3\Logger\FileLogger\FileLogger
 */
class FileLoggerTest extends TestCase {

	private string $tmpDir = '';

	protected function setUp(): void {
		$this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'base3_filelogger_test_' . uniqid('', true);
		mkdir($this->tmpDir, 0777, true);
	}

	protected function tearDown(): void {
		$this->rmDir($this->tmpDir);
	}

	public function testLogLevelCreatesDirectoryAndAppendsFormattedLine(): void {
		$logger = $this->makeLoggerWithDir($this->tmpDir);

		$logger->logLevel('info', 'hello world', [
			'scope' => 'app',
			'timestamp' => 1700000000
		]);

		$file = $this->tmpDir . DIRECTORY_SEPARATOR . 'FileLogger' . DIRECTORY_SEPARATOR . 'app.log';

		$this->assertFileExists($file);

		$contents = (string)file_get_contents($file);
		$this->assertSame("2023-11-14 22:13:20; [INFO]; hello world\n", $contents);
	}

	public function testGetScopesListsLogFilesWithoutExtension(): void {
		$logger = $this->makeLoggerWithDir($this->tmpDir);

		$logger->logLevel('info', 'a', ['scope' => 'one', 'timestamp' => 1700000000]);
		$logger->logLevel('info', 'b', ['scope' => 'two', 'timestamp' => 1700000001]);

		$scopes = $logger->getScopes();
		sort($scopes);

		$this->assertSame(['one', 'two'], $scopes);
	}

	public function testGetNumOfScopesReturnsCount(): void {
		$logger = $this->makeLoggerWithDir($this->tmpDir);

		$this->assertSame(0, $logger->getNumOfScopes());

		$logger->logLevel('info', 'a', ['scope' => 'one', 'timestamp' => 1700000000]);
		$logger->logLevel('info', 'b', ['scope' => 'two', 'timestamp' => 1700000001]);

		$this->assertSame(2, $logger->getNumOfScopes());
	}

	public function testGetLogsParsesAndReturnsTailWithReverseOption(): void {
		$logger = $this->makeLoggerWithDir($this->tmpDir);

		$logger->logLevel('debug', 'm1', ['scope' => 'app', 'timestamp' => 1700000000]);
		$logger->logLevel('info', 'm2', ['scope' => 'app', 'timestamp' => 1700000001]);
		$logger->logLevel('error', 'm3', ['scope' => 'app', 'timestamp' => 1700000002]);

		/**
		 * Implementation detail:
		 * - tail($file, $num) returns the last N lines in chronological order (older -> newer).
		 * - getLogs() builds $logs in that order, then reverses only if $reverse=true.
		 *
		 * Therefore:
		 * - reverse=false => older -> newer (for the tail window)
		 * - reverse=true  => newer -> older (for the tail window)
		 */

		$logsNoRev = $logger->getLogs('app', 2, false);
		$this->assertCount(2, $logsNoRev);

		$this->assertSame('2023-11-14 22:13:21', $logsNoRev[0]['timestamp']);
		$this->assertSame('INFO', $logsNoRev[0]['level']);
		$this->assertSame('m2', $logsNoRev[0]['log']);

		$this->assertSame('2023-11-14 22:13:22', $logsNoRev[1]['timestamp']);
		$this->assertSame('ERROR', $logsNoRev[1]['level']);
		$this->assertSame('m3', $logsNoRev[1]['log']);

		$logsRev = $logger->getLogs('app', 2, true);
		$this->assertCount(2, $logsRev);

		$this->assertSame('2023-11-14 22:13:22', $logsRev[0]['timestamp']);
		$this->assertSame('ERROR', $logsRev[0]['level']);
		$this->assertSame('m3', $logsRev[0]['log']);

		$this->assertSame('2023-11-14 22:13:21', $logsRev[1]['timestamp']);
		$this->assertSame('INFO', $logsRev[1]['level']);
		$this->assertSame('m2', $logsRev[1]['log']);
	}

	private function makeLoggerWithDir(string $dir): FileLogger {
		$logger = new FileLogger();

		$rp = new \ReflectionProperty($logger, 'dir');
		$rp->setAccessible(true);
		$rp->setValue($logger, rtrim($dir, DIRECTORY_SEPARATOR));

		return $logger;
	}

	private function rmDir(string $dir): void {
		if ($dir === '' || !is_dir($dir)) {
			return;
		}

		$items = scandir($dir);
		if (!is_array($items)) {
			return;
		}

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$path = $dir . DIRECTORY_SEPARATOR . $item;

			if (is_dir($path)) {
				$this->rmDir($path);
				continue;
			}

			@unlink($path);
		}

		@rmdir($dir);
	}
}
