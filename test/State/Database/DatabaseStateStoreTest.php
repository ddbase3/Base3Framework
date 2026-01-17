<?php declare(strict_types=1);

namespace Base3\Test\State\Database;

use PHPUnit\Framework\TestCase;
use Base3\Database\Api\IDatabase;
use Base3\State\Database\DatabaseStateStore;

/**
 * @covers \Base3\State\Database\DatabaseStateStore
 */
class DatabaseStateStoreTest extends TestCase {

	/**
	 * @param array<int,string> $queries
	 */
	private function makeDbStub(array &$queries, array $singleQueryMap = [], array $listQueryResult = [], int $affectedRows = 0): IDatabase {
		return new class($queries, $singleQueryMap, $listQueryResult, $affectedRows) implements IDatabase {
			private array $queries;
			private array $singleQueryMap;
			private array $listQueryResult;
			private int $affectedRows;

			public function __construct(array &$queries, array $singleQueryMap, array $listQueryResult, int $affectedRows) {
				$this->queries = &$queries;
				$this->singleQueryMap = $singleQueryMap;
				$this->listQueryResult = $listQueryResult;
				$this->affectedRows = $affectedRows;
			}

			public function connect(): void {
				$this->queries[] = 'connect';
			}

			public function connected(): bool {
				return true;
			}

			public function disconnect(): void {
				$this->queries[] = 'disconnect';
			}

			public function nonQuery(string $query): void {
				$this->queries[] = $query;
			}

			public function scalarQuery(string $query): mixed {
				$this->queries[] = $query;
				return null;
			}

			public function singleQuery(string $query): ?array {
				$this->queries[] = $query;

				foreach ($this->singleQueryMap as $needle => $row) {
					if (str_contains($query, (string)$needle)) {
						return $row;
					}
				}

				return null;
			}

			public function &listQuery(string $query): array {
				$this->queries[] = $query;
				return $this->listQueryResult;
			}

			public function &multiQuery(string $query): array {
				$this->queries[] = $query;
				$empty = [];
				return $empty;
			}

			public function affectedRows(): int {
				return $this->affectedRows;
			}

			public function insertId(): int|string {
				return 0;
			}

			public function escape(string $str): string {
				// Keep it deterministic for assertions.
				return 'ESC(' . $str . ')';
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

	private function assertContainsQuery(array $queries, string $needle): void {
		foreach ($queries as $q) {
			if (is_string($q) && str_contains($q, $needle)) {
				$this->assertTrue(true);
				return;
			}
		}
		$this->fail("Expected to find query containing: {$needle}");
	}

	public function testEnsureReadyConnectsAndCreatesTableOnce(): void {
		$queries = [];
		$db = $this->makeDbStub($queries);

		$s = new DatabaseStateStore($db, 't_statestore');

		$s->has('a');
		$s->has('b');

		// connect should be called on each operation
		$this->assertGreaterThanOrEqual(2, count(array_filter($queries, static fn($q) => $q === 'connect')));

		// CREATE TABLE should happen only once (initialized flag)
		$createCount = 0;
		foreach ($queries as $q) {
			if (is_string($q) && str_contains($q, 'CREATE TABLE IF NOT EXISTS `t_statestore`')) {
				$createCount++;
			}
		}
		$this->assertSame(1, $createCount);
	}

	public function testGetReturnsDefaultWhenMissing(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [
			'FROM `t_statestore`' => null
		]);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertSame('d', $s->get('k1', 'd'));

		$this->assertContainsQuery($queries, "WHERE `key` = 'ESC(k1)'");
	}

	public function testGetDeletesExpiredRowAndReturnsDefault(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [
			'SELECT `value`, `expires_at`' => [
				'value' => '{"x":1}',
				'expires_at' => '2000-01-01 00:00:00'
			]
		], [], 1);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertSame('d', $s->get('k_expired', 'd'));

		$this->assertContainsQuery($queries, "DELETE FROM `t_statestore`");
		$this->assertContainsQuery($queries, "WHERE `key` = 'ESC(k_expired)'");
	}

	public function testHasDeletesExpiredRowAndReturnsFalse(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [
			'SELECT `expires_at`' => [
				'expires_at' => '2000-01-01 00:00:00'
			]
		], [], 1);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertFalse($s->has('k_expired'));

		$this->assertContainsQuery($queries, "DELETE FROM `t_statestore`");
		$this->assertContainsQuery($queries, "WHERE `key` = 'ESC(k_expired)'");
	}

	public function testSetWritesInsertOnDuplicateWithExpiresSqlNull(): void {
		$queries = [];
		$db = $this->makeDbStub($queries);

		$s = new DatabaseStateStore($db, 't_statestore');

		$s->set('k1', ['a' => 1], null);

		$this->assertContainsQuery($queries, "INSERT INTO `t_statestore` (`key`, `value`, `updated_at`, `expires_at`)");
		$this->assertContainsQuery($queries, "VALUES ('ESC(k1)', 'ESC({\"a\":1})', NOW(), NULL)");
		$this->assertContainsQuery($queries, "ON DUPLICATE KEY UPDATE");
	}

	public function testSetWritesExpiresNowForZeroOrNegativeTtl(): void {
		$queries = [];
		$db = $this->makeDbStub($queries);

		$s = new DatabaseStateStore($db, 't_statestore');

		$s->set('k1', 'v', 0);
		$this->assertContainsQuery($queries, "VALUES ('ESC(k1)', 'ESC(\"v\")', NOW(), NOW())");

		$queries = [];
		$db = $this->makeDbStub($queries);
		$s = new DatabaseStateStore($db, 't_statestore');

		$s->set('k2', 'v', -5);
		$this->assertContainsQuery($queries, "VALUES ('ESC(k2)', 'ESC(\"v\")', NOW(), NOW())");
	}

	public function testSetWritesDateAddForPositiveTtl(): void {
		$queries = [];
		$db = $this->makeDbStub($queries);

		$s = new DatabaseStateStore($db, 't_statestore');

		$s->set('k1', 'v', 123);

		$this->assertContainsQuery($queries, "DATE_ADD(NOW(), INTERVAL 123 SECOND)");
	}

	public function testDeleteReturnsTrueWhenAffectedRowsGreaterThanZero(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [], [], 2);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertTrue($s->delete('k1'));
		$this->assertContainsQuery($queries, "DELETE FROM `t_statestore`");
	}

	public function testDeleteReturnsFalseWhenAffectedRowsIsZero(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [], [], 0);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertFalse($s->delete('k1'));
	}

	public function testSetIfNotExistsReturnsTrueWhenAffectedRowsIsOneOrTwo(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [], [], 1);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertTrue($s->setIfNotExists('k1', 'v', 5));
		$this->assertContainsQuery($queries, "ON DUPLICATE KEY UPDATE");

		$queries = [];
		$db = $this->makeDbStub($queries, [], [], 2);
		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertTrue($s->setIfNotExists('k2', 'v', 5));
	}

	public function testSetIfNotExistsReturnsFalseWhenAffectedRowsIsZero(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [], [], 0);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertFalse($s->setIfNotExists('k1', 'v', 5));
	}

	public function testListKeysReturnsArrayFromDbAndBuildsLikeQuery(): void {
		$queries = [];
		$list = ['a', 'a.b', 'a.c'];

		$db = $this->makeDbStub($queries, [], $list);

		$s = new DatabaseStateStore($db, 't_statestore');

		$out = $s->listKeys('a.');
		$this->assertSame($list, $out);

		$this->assertContainsQuery($queries, "WHERE `key` LIKE 'ESC(a.)%'");
		$this->assertContainsQuery($queries, "ORDER BY `key` ASC");
	}

	public function testDecodeReturnsDefaultOnInvalidJson(): void {
		$queries = [];
		$db = $this->makeDbStub($queries, [
			'SELECT `value`, `expires_at`' => [
				'value' => '{invalid',
				'expires_at' => null
			]
		]);

		$s = new DatabaseStateStore($db, 't_statestore');

		$this->assertSame('d', $s->get('k1', 'd'));
	}
}
