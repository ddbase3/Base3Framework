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

namespace Base3\Test\Util\MailParser;

use Base3\CoreFoundation\Util\MailParser\MailPart;
use PHPUnit\Framework\TestCase;

final class MailPartEdgeCoverageTest extends TestCase {

	public function testEmptyInputIsAnEmptyBodyWithNoHeaders(): void {
		$part = new MailPart('');
		$this->assertSame([], $part->getHeaders());
		$this->assertSame('', $part->getBody());
		$this->assertTrue($part->isEmpty());
	}

	public function testFoldedHeadersPreserveOriginalHeaderOrder(): void {
		$part = new MailPart("X-First: one\n\tcontinued\nX-Second: two\n\nbody");
		$this->assertSame([
			['name' => 'X-First', 'value' => 'one continued'],
			['name' => 'X-Second', 'value' => 'two']
		], $part->getHeaders());
		$this->assertSame('body', $part->getBody());
	}

	public function testUnexpectedHeaderlessLineStartsBody(): void {
		$part = new MailPart("X-First: one\nUnexpected body\nX-Text: following");
		$this->assertCount(1, $part->getHeaders());
		$this->assertSame("Unexpected body\nX-Text: following", $part->getBody());
	}

	public function testBlankSeparatorPreservesMultilineBody(): void {
		$part = new MailPart("Subject: test\n\nline one\n\nline three");
		$this->assertSame("line one\n\nline three", $part->getBody());
		$this->assertFalse($part->isEmpty());
	}

	public function testOutputMethodDoesNotMutateParsedPart(): void {
		$part = new MailPart("Subject: test\n\nhello");
		$before = [$part->getHeaders(), $part->getBody()];
		$part->output();
		$this->assertSame($before, [$part->getHeaders(), $part->getBody()]);
	}
}
