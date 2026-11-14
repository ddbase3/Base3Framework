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

use Base3\Core\Util\MailParser\MailParser;
use Base3\CoreFoundation\Util\MailParser\MailPart;
use PHPUnit\Framework\TestCase;

final class MailParserMultipartCoverageTest extends TestCase {

	private function message(): string {
		return "MIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"BOUNDARY\"\n\n"
			. "--BOUNDARY\nContent-Type: text/plain\n\nFirst\n"
			. "--BOUNDARY\nContent-Type: text/plain\n\nSecond\n"
			. "--BOUNDARY--\n";
	}

	public function testQuotedMultipartBoundaryProducesIndividualParts(): void {
		$mail = new MailParser();
		$mail->fromString($this->message());
		$parts = $mail->getParts();
		$this->assertCount(3, $parts);
		$this->assertInstanceOf(MailPart::class, $parts[0]);
		$this->assertSame('First', trim($parts[1]->getBody()));
		$this->assertSame('Second', trim($parts[2]->getBody()));
	}

	public function testMultipartToStringIndicatesPartCountAndHeaders(): void {
		$mail = new MailParser();
		$mail->fromString($this->message());
		$string = $mail->toString();
		$this->assertStringContainsString('[--3--]', $string);
		$this->assertStringContainsString('Content-Type', $string);
		$this->assertStringContainsString('&nbsp;', $string);
	}

	public function testConstructorAcceptsExistingMailFile(): void {
		$file = tempnam(sys_get_temp_dir(), 'base3-mail-');
		$this->assertNotSame(false, $file);
		try {
			file_put_contents($file, "Subject: Example\n\nHello from file");
			$mail = new MailParser($file);
			$this->assertInstanceOf(MailPart::class, $mail->getParts());
			$this->assertSame('Hello from file', $mail->getParts()->getBody());
		} finally { unlink($file); }
	}

	public function testSinglePartFormattingUsesHeadersAndBodyLength(): void {
		$mail = new MailParser();
		$mail->fromString("Subject: One\n\nHello");
		$this->assertStringContainsString('Size: 5', $mail->toString());
		$this->assertStringContainsString('Subject', $mail->toString());
	}
}
