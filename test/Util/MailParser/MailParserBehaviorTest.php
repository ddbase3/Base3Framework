<?php declare(strict_types=1);

namespace Base3\Test\Util\MailParser;

use PHPUnit\Framework\TestCase;

final class MailParserBehaviorTest extends TestCase {

	public function testPlainMessageIsSingleMailPart(): void {
		$p=new \Base3\Util\MailParser\MailParser();
		$p->fromString("Subject: Test\n\nHello world");
		$this->assertInstanceOf(\Base3\Util\MailParser\MailPart::class,$p->getParts());
		$this->assertSame('Hello world',$p->getParts()->getBody());
		$this->assertStringContainsString('Size: ',$p->toString());
	}

	public function testUnquotedMultipartBoundaryIsTreatedAsSinglePart(): void {
		$content="Content-Type: multipart/mixed; boundary=qwerty\n\nBody";
		$p=new \Base3\Util\MailParser\MailParser();
		$p->fromString($content);
		$this->assertInstanceOf(\Base3\Util\MailParser\MailPart::class,$p->getParts());
		$this->assertSame('Body',$p->getParts()->getBody());
	}


}
