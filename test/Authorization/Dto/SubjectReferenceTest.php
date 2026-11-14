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

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class SubjectReferenceTest extends TestCase {

	public function testAllSupportedSubjectKindsAndNativeIds(): void {
		foreach (['user', 'group', 'role', 'agent', 'service'] as $kind) {
			$subject = new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host:one', $kind, 0);
			$this->assertSame($kind, $subject->kind);
			$this->assertSame(0, $subject->id);
		}
	}

	public function testRejectsUnknownKind(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host', 'administrator', 42);
	}

	public function testRejectsEmptyIdentifier(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host', 'user', '');
	}

	public function testRejectsBlankAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('  ', 'user', '42');
	}

}
