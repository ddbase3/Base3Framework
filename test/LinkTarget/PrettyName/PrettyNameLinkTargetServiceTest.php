<?php declare(strict_types=1);

namespace Base3\Test\LinkTarget\PrettyName;

use PHPUnit\Framework\TestCase;

final class PrettyNameLinkTargetServiceTest extends TestCase {

	public function testUrlEncodesOutputAndName(): void {
		$s = new \Base3\LinkTarget\PrettyName\PrettyNameLinkTargetService();
		$this->assertSame('hello%20world.html?a=1', $s->getLink(['name'=>'hello world','out'=>'html'], ['a'=>1]));
		$this->assertSame('contact.php', $s->getLink(['name'=>'contact']));
		$this->assertSame('.php', $s->getLink([]));
	}

}
