<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Logger\Api\ILogger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Base3\Logger\LoggerBridgeTrait
 */
class LoggerBridgeTraitTest extends TestCase {

	public function testPsr3ConvenienceMethodsCallLogLevelWithCorrectLevel(): void {
		$calls = [];

		$logger = new class($calls) {
			use \Base3\Logger\LoggerBridgeTrait;

			private array $calls;

			public function __construct(array &$calls) {
				$this->calls = &$calls;
			}

			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = [
					'level' => $level,
					'message' => (string)$message,
					'context' => $context
				];
			}
		};

		$logger->emergency('m1');
		$logger->alert('m2');
		$logger->critical('m3');
		$logger->error('m4');
		$logger->warning('m5');
		$logger->notice('m6');
		$logger->info('m7');
		$logger->debug('m8');

		$this->assertSame([
			['level' => ILogger::EMERGENCY, 'message' => 'm1', 'context' => []],
			['level' => ILogger::ALERT, 'message' => 'm2', 'context' => []],
			['level' => ILogger::CRITICAL, 'message' => 'm3', 'context' => []],
			['level' => ILogger::ERROR, 'message' => 'm4', 'context' => []],
			['level' => ILogger::WARNING, 'message' => 'm5', 'context' => []],
			['level' => ILogger::NOTICE, 'message' => 'm6', 'context' => []],
			['level' => ILogger::INFO, 'message' => 'm7', 'context' => []],
			['level' => ILogger::DEBUG, 'message' => 'm8', 'context' => []],
		], $calls);
	}

	public function testLegacyLogMapsToInfoLevelAndAddsScopeAndTimestamp(): void {
		$calls = [];

		$logger = new class($calls) {
			use \Base3\Logger\LoggerBridgeTrait;

			private array $calls;

			public function __construct(array &$calls) {
				$this->calls = &$calls;
			}

			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = [
					'level' => $level,
					'message' => (string)$message,
					'context' => $context
				];
			}
		};

		$ok = $logger->log('myscope', 'hello', 1700000000);
		$this->assertTrue($ok);

		$this->assertCount(1, $calls);
		$this->assertSame(ILogger::INFO, $calls[0]['level']);
		$this->assertSame('hello', $calls[0]['message']);
		$this->assertSame([
			'scope' => 'myscope',
			'timestamp' => 1700000000
		], $calls[0]['context']);
	}

	public function testDefaultLegacyMethodsReturnEmptyValues(): void {
		$logger = new class {
			use \Base3\Logger\LoggerBridgeTrait;

			public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
				// no-op
			}
		};

		$this->assertSame([], $logger->getScopes());
		$this->assertSame(0, $logger->getNumOfScopes());
		$this->assertSame([], $logger->getLogs('any', 10, true));
	}
}
