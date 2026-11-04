<?php declare(strict_types=1);

namespace Base3\Test\LinkTarget\Standard;

use PHPUnit\Framework\TestCase;

final class StandardLinkTargetServiceTest extends TestCase {

	public function testUsesQueryEncodingAndDefaultOutput(): void {
		$s = new \Base3\LinkTarget\Standard\StandardLinkTargetService();
		$this->assertSame('?name=imprint&out=php', $s->getLink(['name' => 'imprint']));
		$this->assertSame('?name=hello+world&out=json&a=1', $s->getLink(['name'=>'hello world', 'out'=>'json'], ['a'=>1]));
	}

	public function testCustomParamsMayOverrideBaseTargetValues(): void {
		$s = new \Base3\LinkTarget\Standard\StandardLinkTargetService();
		$this->assertSame('?name=other&out=xml', $s->getLink(['name'=>'old'], ['name'=>'other', 'out'=>'xml']));
	}

}
