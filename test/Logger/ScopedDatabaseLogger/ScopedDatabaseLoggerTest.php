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

namespace Base3\Test\Logger\ScopedDatabaseLogger;

use Base3\Core\Logger\ScopedDatabaseLogger\ScopedDatabaseLogger;
use Base3\Test\Support\RecordingDatabaseStub;
use PHPUnit\Framework\TestCase;

final class ScopedDatabaseLoggerTest extends TestCase {

	public function testWritesEscapedScopeLevelMessageAndExplicitTime(): void {
		$db = new RecordingDatabaseStub();
		$logger = new ScopedDatabaseLogger($db);
		$logger->logLevel('notice', "hello 'world'", ['scope' => "proj'ect", 'timestamp' => '2026-11-01 12:00:00']);
		$this->assertSame(2, $db->connectCalls);
		$this->assertCount(2, $db->nonQueries);
		$this->assertStringContainsString('CREATE TABLE IF NOT EXISTS base3_log', $db->nonQueries[0]);
		$this->assertStringContainsString("proj\\'ect", $db->nonQueries[1]);
		$this->assertStringContainsString("hello \\'world\\'", $db->nonQueries[1]);
		$this->assertStringContainsString('2026-11-01 12:00:00', $db->nonQueries[1]);
	}

	public function testUnixTimestampAndDefaultScopeAreNormalized(): void {
		$db = new RecordingDatabaseStub();
		(new ScopedDatabaseLogger($db))->logLevel('debug', 'event', ['timestamp' => 0]);
		$this->assertStringContainsString(date('Y-m-d H:i:s', 0), $db->nonQueries[1]);
		$this->assertStringContainsString("'default'", $db->nonQueries[1]);
	}

	public function testScopesIgnoreIncompleteRowsAndPreserveOrder(): void {
		$db = new RecordingDatabaseStub();
		$db->multiRows = [['scope'=>'first'], ['no_scope'=>'skip'], ['scope'=>'second']];
		$logger = new ScopedDatabaseLogger($db);
		$this->assertSame(['first','second'], $logger->getScopes());
		$this->assertStringContainsString('ORDER BY scope ASC', $db->multiQueries[0]);
		$this->assertSame(2, $logger->getNumOfScopes());
	}

	public function testLogReadingSupportsOrderingLimitAndEscapedScope(): void {
		$db = new RecordingDatabaseStub();
		$db->multiRows = [['timestamp'=>'t', 'scope'=>'a','level'=>'info','log'=>'text']];
		$logger = new ScopedDatabaseLogger($db);
		$this->assertSame([['timestamp'=>'t','scope'=>'a','level'=>'info','log'=>'text']], $logger->getLogs("a'b", 4));
		$this->assertStringContainsString("a\\'b", $db->multiQueries[0]);
		$this->assertStringContainsString('ORDER BY id DESC', $db->multiQueries[0]);
		$this->assertStringContainsString('LIMIT 4', $db->multiQueries[0]);
		$this->assertCount(1, $logger->getLogs('a', 1, false));
		$this->assertStringContainsString('ORDER BY id ASC', $db->multiQueries[1]);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
