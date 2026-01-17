<?php declare(strict_types=1);

namespace Base3\Test\Logger\DatabaseLogger;

use Base3\Database\Api\IDatabase;
use Base3\Logger\DatabaseLogger\DatabaseLogger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Base3\Logger\DatabaseLogger\DatabaseLogger
 */
#[AllowMockObjectsWithoutExpectations]
class DatabaseLoggerTest extends TestCase {

	public function testLogLevelEnsuresTableAndInsertsEscapedRow(): void {
		$calls = [];

		$db = $this->makeDbSpy($calls);
		$logger = new DatabaseLogger($db);

		$logger->logLevel('info', "hello 'world'", [
			'scope' => 'my-scope',
			'timestamp' => 1700000000
		]);

		$this->assertNotEmpty($calls);

		// 1) CREATE TABLE (scope sanitized: "-" -> "_")
		$this->assertSame('connect', $calls[0]['m']);
		$this->assertSame('nonQuery', $calls[1]['m']);
		$this->assertStringContainsString('CREATE TABLE IF NOT EXISTS logger_my_scope', $calls[1]['sql']);

		// 2) INSERT
		$this->assertSame('connect', $calls[2]['m']);
		$this->assertSame('nonQuery', $calls[3]['m']);
		$this->assertStringContainsString("INSERT INTO logger_my_scope", $calls[3]['sql']);
		$this->assertStringContainsString("VALUES (1700000000", $calls[3]['sql']);

		// escape() is identity in our spy; ensure raw values appear in SQL
		$this->assertStringContainsString("'info'", $calls[3]['sql']);
		$this->assertStringContainsString("hello 'world'", $calls[3]['sql']);
	}

	public function testGetScopesParsesShowTablesResult(): void {
		$calls = [];

		$db = $this->makeDbSpy($calls, [
			"SHOW TABLES LIKE 'logger_%'" => [
				['logger_default'],
				['logger_app'],
				['something_else']
			]
		]);

		$logger = new DatabaseLogger($db);

		$scopes = $logger->getScopes();
		sort($scopes);

		$this->assertSame(['app', 'default'], $scopes);

		$this->assertSame('connect', $calls[0]['m']);
		$this->assertSame('multiQuery', $calls[1]['m']);
		$this->assertSame("SHOW TABLES LIKE 'logger_%'", $calls[1]['sql']);
	}

	public function testGetNumOfScopesReturnsCount(): void {
		$calls = [];

		$db = $this->makeDbSpy($calls, [
			"SHOW TABLES LIKE 'logger_%'" => [
				['logger_a'],
				['logger_b'],
			]
		]);

		$logger = new DatabaseLogger($db);

		$this->assertSame(2, $logger->getNumOfScopes());
	}

	public function testGetLogsCreatesTableAndSelectsOrderedRowsAndFormatsTimestamps(): void {
		$calls = [];

		$db = $this->makeDbSpy($calls, [
			"SELECT `timestamp`, level, log FROM logger_app ORDER BY id DESC LIMIT 2" => [
				['timestamp' => 1700000002, 'level' => 'error', 'log' => 'm3'],
				['timestamp' => 1700000001, 'level' => 'info', 'log' => 'm2'],
			]
		]);

		$logger = new DatabaseLogger($db);

		$logs = $logger->getLogs('app', 2, true);

		$this->assertCount(2, $logs);

		$this->assertSame('2023-11-14 22:13:22', $logs[0]['timestamp']);
		$this->assertSame('error', $logs[0]['level']);
		$this->assertSame('m3', $logs[0]['log']);

		$this->assertSame('2023-11-14 22:13:21', $logs[1]['timestamp']);
		$this->assertSame('info', $logs[1]['level']);
		$this->assertSame('m2', $logs[1]['log']);

		// Ensure SELECT used DESC when reverse=true
		$selectCall = null;
		foreach ($calls as $c) {
			if ($c['m'] === 'multiQuery' && str_starts_with($c['sql'], 'SELECT `timestamp`')) {
				$selectCall = $c['sql'];
				break;
			}
		}
		$this->assertNotNull($selectCall);
		$this->assertSame("SELECT `timestamp`, level, log FROM logger_app ORDER BY id DESC LIMIT 2", $selectCall);
	}

	/**
	 * Creates a lightweight IDatabase spy:
	 * - connect(): void
	 * - nonQuery(): void
	 * - singleQuery(): ?array (unused here)
	 * - multiQuery(): array
	 * - listQuery(): &array (unused)
	 * - affectedRows(): int (unused)
	 * - escape(): string (identity)
	 */
	private function makeDbSpy(array &$calls, array $multiQueryMap = []): IDatabase {
		return new class($calls, $multiQueryMap) implements IDatabase {

			private array $calls;
			private array $multiQueryMap;

			public function __construct(array &$calls, array $multiQueryMap) {
				$this->calls = &$calls;
				$this->multiQueryMap = $multiQueryMap;
			}

			public function connect(): void {
				$this->calls[] = ['m' => 'connect'];
			}

			public function connected(): bool {
				return true;
			}

			public function disconnect(): void {
			}

			public function nonQuery(string $query): void {
				$this->calls[] = ['m' => 'nonQuery', 'sql' => $query];
			}

			public function scalarQuery(string $query): mixed {
				return null;
			}

			public function singleQuery(string $query): ?array {
				return null;
			}

			public function &listQuery(string $query): array {
				$this->calls[] = ['m' => 'listQuery', 'sql' => $query];
				$out = [];
				return $out;
			}

			public function &multiQuery(string $query): array {
				$this->calls[] = ['m' => 'multiQuery', 'sql' => $query];

				$out = $this->multiQueryMap[$query] ?? [];
				return $out;
			}

			public function affectedRows(): int {
				return 0;
			}

			public function insertId(): int|string {
				return 0;
			}

			public function escape(string $str): string {
				return $str;
			}

			public function isError(): bool {
				return false;
			}

			public function errorNumber(): int {
				return 0;
			}

			public function errorMessage(): string {
				return '';
			}
		};
	}
}
