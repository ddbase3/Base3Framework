<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Logger\DatabaseLogger;

use Base3\CoreFoundation\Database\Api\IDatabase;
use Base3\Core\Logger\DatabaseLogger\DatabaseLogger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

final class DatabaseLoggerTestDouble1 implements IDatabase {

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

			public function beginTransaction(): void {
				$this->calls[] = ['m' => 'beginTransaction'];
			}

			public function commit(): void {
				$this->calls[] = ['m' => 'commit'];
			}

			public function rollback(): void {
				$this->calls[] = ['m' => 'rollback'];
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
		}
