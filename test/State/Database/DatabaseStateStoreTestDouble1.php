<?php declare(strict_types=1);

namespace Base3\Test\State\Database;

use PHPUnit\Framework\TestCase;
use Base3\Database\Api\IDatabase;
use Base3\State\Database\DatabaseStateStore;

final class DatabaseStateStoreTestDouble1 implements IDatabase {
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

			public function beginTransaction(): void {
				$this->queries[] = 'beginTransaction';
			}

			public function commit(): void {
				$this->queries[] = 'commit';
			}

			public function rollback(): void {
				$this->queries[] = 'rollback';
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
		}
