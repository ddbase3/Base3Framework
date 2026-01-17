<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Api\ICheck;
use Base3\Logger\Api\ILogger;
use Base3\Logger\LoggerProxy;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Base3\Logger\LoggerProxy
 */
#[AllowMockObjectsWithoutExpectations]
class LoggerProxyTest extends TestCase {

	public function testPsr3MethodsDelegateToConnector(): void {
		$calls = [];
		$connector = $this->makeLoggerSpy($calls);

		$proxy = new LoggerProxy($connector);

		$proxy->emergency('e1', ['a' => 1]);
		$proxy->alert('a1', []);
		$proxy->critical('c1', ['x' => 'y']);
		$proxy->error('er1');
		$proxy->warning('w1');
		$proxy->notice('n1');
		$proxy->info('i1');
		$proxy->debug('d1', ['k' => 'v']);
		$proxy->logLevel('info', 'lvl', ['scope' => 's']);

		$this->assertSame([
			['m' => 'emergency', 'message' => 'e1', 'context' => ['a' => 1]],
			['m' => 'alert', 'message' => 'a1', 'context' => []],
			['m' => 'critical', 'message' => 'c1', 'context' => ['x' => 'y']],
			['m' => 'error', 'message' => 'er1', 'context' => []],
			['m' => 'warning', 'message' => 'w1', 'context' => []],
			['m' => 'notice', 'message' => 'n1', 'context' => []],
			['m' => 'info', 'message' => 'i1', 'context' => []],
			['m' => 'debug', 'message' => 'd1', 'context' => ['k' => 'v']],
			['m' => 'logLevel', 'level' => 'info', 'message' => 'lvl', 'context' => ['scope' => 's']],
		], $calls);
	}

	public function testLegacyMethodsDelegateAndReturnValuesArePassedThrough(): void {
		$calls = [];
		$connector = $this->makeLoggerSpy($calls);

		$proxy = new LoggerProxy($connector);

		$ok = $proxy->log('scope', 'hello', 1700000000);
		$this->assertTrue($ok);

		$scopes = $proxy->getScopes();
		$this->assertSame(['a', 'b'], $scopes);

		$num = $proxy->getNumOfScopes();
		$this->assertSame(2, $num);

		$logs = $proxy->getLogs('scope', 2, false);
		$this->assertSame([
			['timestamp' => 't1', 'level' => 'INFO', 'log' => 'x'],
			['timestamp' => 't2', 'level' => 'ERROR', 'log' => 'y'],
		], $logs);

		$this->assertSame('log', $calls[0]['m']);
		$this->assertSame('getScopes', $calls[1]['m']);
		$this->assertSame('getNumOfScopes', $calls[2]['m']);
		$this->assertSame('getLogs', $calls[3]['m']);
	}

	public function testCheckDependenciesDelegatesWhenConnectorImplementsICheck(): void {
		$calls = [];

		$connector = new class($calls) implements ILogger, ICheck {

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
		};

		$proxy = new LoggerProxy($connector);

		$out = $proxy->checkDependencies();
		$this->assertSame(['dep1' => true], $out);
		$this->assertSame([['m' => 'checkDependencies']], $calls);
	}

	public function testCheckDependenciesReturnsEmptyArrayWhenConnectorDoesNotImplementICheck(): void {
		$calls = [];
		$connector = $this->makeLoggerSpy($calls);

		$proxy = new LoggerProxy($connector);

		$out = $proxy->checkDependencies();
		$this->assertSame([], $out);
	}

	private function makeLoggerSpy(array &$calls): ILogger {
		return new class($calls) implements ILogger {

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
		};
	}
}
