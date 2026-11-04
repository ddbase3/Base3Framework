<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class AuthorizationCapabilitiesTest extends TestCase {

	public function testPreservesNativeAdministrationCapabilities(): void {
		$c = new \Base3\Authorization\Dto\AuthorizationCapabilities('ilias:one', true, false, true, ['user', 'role'], ['readonly']);
		$this->assertSame('ilias:one', $c->authority);
		$this->assertTrue($c->canRead);
		$this->assertFalse($c->canWrite);
		$this->assertTrue($c->supportsHierarchy);
		$this->assertSame(['user', 'role'], $c->subjectKinds);
		$this->assertSame(['readonly'], $c->limitations);
	}

	public function testLimitationsAreOptional(): void {
		$c = new \Base3\Authorization\Dto\AuthorizationCapabilities('host', false, false, false, []);
		$this->assertSame([], $c->limitations);
	}

}
