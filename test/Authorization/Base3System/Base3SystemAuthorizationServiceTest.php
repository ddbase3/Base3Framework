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

namespace Base3\Test\Authorization\Base3System;

use PHPUnit\Framework\TestCase;

final class Base3SystemAuthorizationServiceTest extends TestCase {

	private function request(?\Base3\CoreFoundation\Authorization\Dto\ResourceReference $resource = null, ?\Base3\CoreFoundation\Authorization\Dto\SubjectReference $subject = null, ?\Base3\CoreFoundation\Authorization\Dto\SubjectReference $onBehalfOf = null): \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest {
		return new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('base3', \Base3\CoreFoundation\Usermanager\Permission::for('catalog', 'edit'), $resource, $subject, [], [], $onBehalfOf);
	}

	public function testStandalonePermissionAndScopeSpecificAdmin(): void {
		$identity = new \Base3\Test\Authorization\PermissionUsermanagerStub();
		$s = new \Base3\Core\Authorization\Base3System\Base3SystemAuthorizationService($identity);
		$this->assertSame('base3', $s->getDefaultAuthority());
		$this->assertSame('no_permission', $s->decide($this->request())->reasonCode);
		$identity->permissions = [\Base3\CoreFoundation\Usermanager\Permission::for('catalog', 'edit')];
		$this->assertSame('role_permission', $s->decide($this->request())->reasonCode);
		$identity->permissions = [\Base3\CoreFoundation\Usermanager\Permission::for('catalog', 'admin')];
		$this->assertTrue($s->decide($this->request())->isAllowed());
		$identity->permissions = [\Base3\CoreFoundation\Usermanager\Permission::for('system', 'admin')];
		$this->assertSame('system_administrator', $s->decide($this->request())->reasonCode);
	}

	public function testDeniesDelegatedAndResourcePermissions(): void {
		$identity = new \Base3\Test\Authorization\PermissionUsermanagerStub();
		$identity->permissions = [\Base3\CoreFoundation\Usermanager\Permission::for('system', 'admin')];
		$s = new \Base3\Core\Authorization\Base3System\Base3SystemAuthorizationService($identity);
		$other = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('other', \Base3\CoreFoundation\Usermanager\Permission::for('catalog', 'edit'));
		$this->assertSame('unknown_authority', $s->decide($other)->reasonCode);
		$subject = new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('base3', 'user', 1);
		$this->assertSame('unsupported_principal', $s->decide($this->request(null, $subject))->reasonCode);
		$this->assertSame('unsupported_principal', $s->decide($this->request(null, null, $subject))->reasonCode);
		$resource = new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('base3', 'course', 5);
		$this->assertSame('resource_authorization_not_supported', $s->decide($this->request($resource))->reasonCode);
	}

	public function testBatchPreservesKeysAndRejectsNonRequests(): void {
		$s = new \Base3\Core\Authorization\Base3System\Base3SystemAuthorizationService(new \Base3\Test\Authorization\PermissionUsermanagerStub());
		$this->assertSame(['second', 'first'], array_keys($s->decideMany(['second' => $this->request(), 'first' => $this->request()])));
		$this->expectException(\InvalidArgumentException::class);
		$s->decideMany([null]);
	}

}

require_once __DIR__ . '/../PermissionUsermanagerStub.php';
