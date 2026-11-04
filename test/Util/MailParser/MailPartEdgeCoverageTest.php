<?php declare(strict_types=1);

namespace Base3\Test\Util\MailParser;

use Base3\Util\MailParser\MailPart;
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
