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

final class MailPartTest extends TestCase {

	public function testParsesHeadersFoldedValuesAndBody(): void {
		$p=new \Base3\CoreFoundation\Util\MailParser\MailPart("Subject: Hello\r\nX-Test: part\r\n  two\r\n\r\nBody first\nBody second");
		$this->assertFalse($p->isEmpty());
		$this->assertSame('Body first' . "\n" . 'Body second',$p->getBody());
		$this->assertSame([['name'=>'Subject','value'=>'Hello'],['name'=>'X-Test','value'=>'part two']],$p->getHeaders());
		$this->assertStringContainsString('Size: ',$p->toString());
	}

	public function testWithoutSeparatorTreatsUnrecognizedLineAsBody(): void {
		$p=new \Base3\CoreFoundation\Util\MailParser\MailPart("Subject: Hello\nBody without header separator");
		$this->assertSame('Body without header separator',$p->getBody());
		$this->assertCount(1,$p->getHeaders());
	}

	public function testEmptyBodyIsDetected(): void {
		$p=new \Base3\CoreFoundation\Util\MailParser\MailPart("Subject: Empty\n\n");
		$this->assertTrue($p->isEmpty());
		$this->assertSame('', $p->getBody());
	}

}
