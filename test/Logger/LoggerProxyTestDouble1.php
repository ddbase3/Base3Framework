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

final class LoggerProxyTestDouble1 implements ILogger, ICheck {

			private array $calls;

			public function __construct(array &$calls) {
				$this->calls = &$calls;
			}

			// --- PSR-3 ---
			public function emergency(string|\Stringable $message, array $context = []): void {}
			public function alert(string|\Stringable $message, array $context = []): void {}
			public function critical(string|\Stringable $message, array $context = []): void {}
			public function error(string|\Stringable $message, array $context = []): void {}
			public function warning(string|\Stringable $message, array $context = []): void {}
			public function notice(string|\Stringable $message, array $context = []): void {}
			public function info(string|\Stringable $message, array $context = []): void {}
			public function debug(string|\Stringable $message, array $context = []): void {}
			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {}

			// --- legacy ---
			public function log(string $scope, string $log, ?int $timestamp = null): bool { return true; }
			public function getScopes(): array { return []; }
			public function getNumOfScopes() { return 0; }
			public function getLogs(string $scope, int $num = 50, bool $reverse = true): array { return []; }

			// --- ICheck ---
			public function checkDependencies() {
				$this->calls[] = ['m' => 'checkDependencies'];
				return ['dep1' => true];
			}
		}
