<?php declare(strict_types=1);

namespace Base3\Test\Logger;

use Base3\Logger\Api\ILogger;
use PHPUnit\Framework\TestCase;

final class AbstractLoggerBehaviorTest extends TestCase {

	public function testSeverityConvenienceMethodsPreserveContext(): void {
		$logger = new RecordingLoggerFixture();
		$methods = ['emergency'=>ILogger::EMERGENCY,'alert'=>ILogger::ALERT,'critical'=>ILogger::CRITICAL,'error'=>ILogger::ERROR,'warning'=>ILogger::WARNING,'notice'=>ILogger::NOTICE,'info'=>ILogger::INFO,'debug'=>ILogger::DEBUG];
		foreach ($methods as $method=>$level) {
			$logger->$method('message',['context'=>1]);
			$this->assertSame([$level,'message',['context'=>1]],$logger->records[count($logger->records)-1]);
		}
		$this->assertCount(8,$logger->records);
	}

	public function testLegacyScopeLogMapsToInfoWithProvidedTimestamp(): void {
		$logger = new RecordingLoggerFixture();
		$this->assertTrue($logger->log('scope','written',123456));
		$this->assertSame([ILogger::INFO,'written',['scope'=>'scope','timestamp'=>123456]],$logger->records[0]);
	}

	public function testDefaultScopeListingIsEmpty(): void {
		$logger = new RecordingLoggerFixture();
		$this->assertSame([],$logger->getScopes());
		$this->assertSame(0,$logger->getNumOfScopes());
		$this->assertSame([],$logger->getLogs('missing',20,false));
	}
}

require_once __DIR__ . '/RecordingLoggerFixture.php';
