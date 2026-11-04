<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class SubjectReferenceTest extends TestCase {

	public function testAllSupportedSubjectKindsAndNativeIds(): void {
		foreach (['user', 'group', 'role', 'agent', 'service'] as $kind) {
			$subject = new \Base3\Authorization\Dto\SubjectReference('host:one', $kind, 0);
			$this->assertSame($kind, $subject->kind);
			$this->assertSame(0, $subject->id);
		}
	}

	public function testRejectsUnknownKind(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\SubjectReference('host', 'administrator', 42);
	}

	public function testRejectsEmptyIdentifier(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\SubjectReference('host', 'user', '');
	}

	public function testRejectsBlankAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\SubjectReference('  ', 'user', '42');
	}

}
