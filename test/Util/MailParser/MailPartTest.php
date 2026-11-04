<?php declare(strict_types=1);

namespace Base3\Test\Util\MailParser;

use PHPUnit\Framework\TestCase;

final class MailPartTest extends TestCase {

	public function testParsesHeadersFoldedValuesAndBody(): void {
		$p=new \Base3\Util\MailParser\MailPart("Subject: Hello\r\nX-Test: part\r\n  two\r\n\r\nBody first\nBody second");
		$this->assertFalse($p->isEmpty());
		$this->assertSame('Body first' . "\n" . 'Body second',$p->getBody());
		$this->assertSame([['name'=>'Subject','value'=>'Hello'],['name'=>'X-Test','value'=>'part two']],$p->getHeaders());
		$this->assertStringContainsString('Size: ',$p->toString());
	}

	public function testWithoutSeparatorTreatsUnrecognizedLineAsBody(): void {
		$p=new \Base3\Util\MailParser\MailPart("Subject: Hello\nBody without header separator");
		$this->assertSame('Body without header separator',$p->getBody());
		$this->assertCount(1,$p->getHeaders());
	}

	public function testEmptyBodyIsDetected(): void {
		$p=new \Base3\Util\MailParser\MailPart("Subject: Empty\n\n");
		$this->assertTrue($p->isEmpty());
		$this->assertSame('', $p->getBody());
	}

}
