<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class ResourceReferenceTest extends TestCase {

	public function testPreservesAuthorityTypeAndNativeIdentifier(): void {
		$resource = new \Base3\Authorization\Dto\ResourceReference('ilias:one', 'course', 0);
		$this->assertSame('ilias:one', $resource->authority);
		$this->assertSame('course', $resource->type);
		$this->assertSame(0, $resource->id);
	}

	public function testRejectsEmptyAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\ResourceReference(' ', 'course', 1);
	}

	public function testRejectsEmptyType(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\ResourceReference('native', '', 1);
	}

	public function testRejectsEmptyIdentifier(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\ResourceReference('native', 'course', '  ');
	}

}
