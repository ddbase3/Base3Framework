<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Api\ICheck;
use Base3\Logger\Api\ILogger;
use Base3\Logger\LoggerProxy;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(\Base3\Logger\LoggerProxy::class)]
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

		$connector = new LoggerProxyTestDouble1($calls);

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
		return new LoggerProxyTestDouble2($calls);
	}
}

require_once __DIR__ . '/LoggerProxyTestDouble1.php';

require_once __DIR__ . '/LoggerProxyTestDouble2.php';
