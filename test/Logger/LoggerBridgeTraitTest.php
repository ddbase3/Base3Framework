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
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(\Base3\CoreFoundation\Logger\LoggerBridgeTrait::class)]
class LoggerBridgeTraitTest extends TestCase {

	public function testPsr3ConvenienceMethodsCallLogLevelWithCorrectLevel(): void {
		$calls = [];

		$logger = new LoggerBridgeTraitTestDouble1($calls);

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

		$logger = new LoggerBridgeTraitTestDouble2($calls);

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
		$logger = new LoggerBridgeTraitTestDouble3();

		$this->assertSame([], $logger->getScopes());
		$this->assertSame(0, $logger->getNumOfScopes());
		$this->assertSame([], $logger->getLogs('any', 10, true));
	}
}

require_once __DIR__ . '/LoggerBridgeTraitTestDouble1.php';

require_once __DIR__ . '/LoggerBridgeTraitTestDouble2.php';

require_once __DIR__ . '/LoggerBridgeTraitTestDouble3.php';
