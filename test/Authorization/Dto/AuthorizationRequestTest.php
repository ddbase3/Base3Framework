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

final class AuthorizationRequestTest extends TestCase {

	public function testClonesPermissionAndKeepsTrustedContext(): void {
		$permission = \Base3\CoreFoundation\Usermanager\Permission::for('course', 'read');
		$resource = new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('host', 'course', 17);
		$subject = new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host', 'user', 'joe');
		$request = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('host', $permission, $resource, $subject, ['limit' => 3], ['source' => 'server']);
		$permission->permission = 'delete';
		$this->assertSame('read', $request->permission->permission);
		$this->assertNotSame($permission, $request->permission);
		$this->assertSame($subject, $request->subject);
		$this->assertSame(['limit' => 3], $request->parameters);
		$this->assertSame(['source' => 'server'], $request->context);
	}

	public function testRejectsResourceFromOtherAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('host', \Base3\CoreFoundation\Usermanager\Permission::for('course', 'read'), new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('other', 'course', 1));
	}

	public function testRejectsBlankAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest(' ', \Base3\CoreFoundation\Usermanager\Permission::for('course', 'read'));
	}

	public function testRejectsMissingScope(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('host', \Base3\CoreFoundation\Usermanager\Permission::for(' ', 'read'));
	}

	public function testRejectsMissingPermission(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('host', \Base3\CoreFoundation\Usermanager\Permission::for('course', ''));
	}

}
