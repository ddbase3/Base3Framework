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

final class AuthorizationGrantTest extends TestCase {

	public function testPreservesSubjectResourceAndInheritance(): void {
		$subject = new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host', 'user', 1);
		$resource = new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('host', 'course', 2);
		$origin = new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('host', 'category', 3);
		$permission = \Base3\CoreFoundation\Usermanager\Permission::for('course', 'read');
		$grant = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationGrant($subject, $permission, $resource, false, true, $origin);
		$this->assertSame($subject, $grant->subject);
		$this->assertSame($resource, $grant->resource);
		$this->assertSame($origin, $grant->origin);
		$this->assertTrue($grant->inherited);
		$this->assertFalse($grant->editable);
		$this->assertSame('read', $grant->permission->permission);
	}

	public function testInheritanceIsUnknownByDefault(): void {
		$grant = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationGrant(
			new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host', 'user', 1),
			\Base3\CoreFoundation\Usermanager\Permission::for('course', 'read'),
			new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('host', 'course', 1),
			true
		);
		$this->assertNull($grant->inherited);
		$this->assertNull($grant->origin);
	}

}
