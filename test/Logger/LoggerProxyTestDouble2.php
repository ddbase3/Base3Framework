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

namespace Base3\Test\Logger;

use Base3\CoreFoundation\Api\ICheck;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\Core\Logger\LoggerProxy;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

final class LoggerProxyTestDouble2 implements ILogger {

			private array $calls;

			public function __construct(array &$calls) {
				$this->calls = &$calls;
			}

			// --- PSR-3 ---
			public function emergency(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'emergency', 'message' => (string)$message, 'context' => $context];
			}

			public function alert(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'alert', 'message' => (string)$message, 'context' => $context];
			}

			public function critical(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'critical', 'message' => (string)$message, 'context' => $context];
			}

			public function error(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'error', 'message' => (string)$message, 'context' => $context];
			}

			public function warning(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'warning', 'message' => (string)$message, 'context' => $context];
			}

			public function notice(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'notice', 'message' => (string)$message, 'context' => $context];
			}

			public function info(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'info', 'message' => (string)$message, 'context' => $context];
			}

			public function debug(string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'debug', 'message' => (string)$message, 'context' => $context];
			}

			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['m' => 'logLevel', 'level' => $level, 'message' => (string)$message, 'context' => $context];
			}

			// --- legacy ---
			public function log(string $scope, string $log, ?int $timestamp = null): bool {
				$this->calls[] = ['m' => 'log', 'scope' => $scope, 'log' => $log, 'timestamp' => $timestamp];
				return true;
			}

			public function getScopes(): array {
				$this->calls[] = ['m' => 'getScopes'];
				return ['a', 'b'];
			}

			public function getNumOfScopes() {
				$this->calls[] = ['m' => 'getNumOfScopes'];
				return 2;
			}

			public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
				$this->calls[] = ['m' => 'getLogs', 'scope' => $scope, 'num' => $num, 'reverse' => $reverse];
				return [
					['timestamp' => 't1', 'level' => 'INFO', 'log' => 'x'],
					['timestamp' => 't2', 'level' => 'ERROR', 'log' => 'y'],
				];
			}
		}
