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

use Base3\CoreFoundation\Logger\Api\ILogger;
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
