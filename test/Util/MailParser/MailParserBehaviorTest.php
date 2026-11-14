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

use PHPUnit\Framework\TestCase;

final class MailParserBehaviorTest extends TestCase {

	public function testPlainMessageIsSingleMailPart(): void {
		$p=new \Base3\Core\Util\MailParser\MailParser();
		$p->fromString("Subject: Test\n\nHello world");
		$this->assertInstanceOf(\Base3\CoreFoundation\Util\MailParser\MailPart::class,$p->getParts());
		$this->assertSame('Hello world',$p->getParts()->getBody());
		$this->assertStringContainsString('Size: ',$p->toString());
	}

	public function testUnquotedMultipartBoundaryIsTreatedAsSinglePart(): void {
		$content="Content-Type: multipart/mixed; boundary=qwerty\n\nBody";
		$p=new \Base3\Core\Util\MailParser\MailParser();
		$p->fromString($content);
		$this->assertInstanceOf(\Base3\CoreFoundation\Util\MailParser\MailPart::class,$p->getParts());
		$this->assertSame('Body',$p->getParts()->getBody());
	}


}
