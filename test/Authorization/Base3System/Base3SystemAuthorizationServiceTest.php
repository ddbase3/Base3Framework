<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Base3System;

use PHPUnit\Framework\TestCase;

final class Base3SystemAuthorizationServiceTest extends TestCase {

	private function request(?\Base3\Authorization\Dto\ResourceReference $resource = null, ?\Base3\Authorization\Dto\SubjectReference $subject = null, ?\Base3\Authorization\Dto\SubjectReference $onBehalfOf = null): \Base3\Authorization\Dto\AuthorizationRequest {
		return new \Base3\Authorization\Dto\AuthorizationRequest('base3', \Base3\Usermanager\Permission::for('catalog', 'edit'), $resource, $subject, [], [], $onBehalfOf);
	}

	public function testStandalonePermissionAndScopeSpecificAdmin(): void {
		$identity = new \Base3\Test\Authorization\PermissionUsermanagerStub();
		$s = new \Base3\Authorization\Base3System\Base3SystemAuthorizationService($identity);
		$this->assertSame('base3', $s->getDefaultAuthority());
		$this->assertSame('no_permission', $s->decide($this->request())->reasonCode);
		$identity->permissions = [\Base3\Usermanager\Permission::for('catalog', 'edit')];
		$this->assertSame('role_permission', $s->decide($this->request())->reasonCode);
		$identity->permissions = [\Base3\Usermanager\Permission::for('catalog', 'admin')];
		$this->assertTrue($s->decide($this->request())->isAllowed());
		$identity->permissions = [\Base3\Usermanager\Permission::for('system', 'admin')];
		$this->assertSame('system_administrator', $s->decide($this->request())->reasonCode);
	}

	public function testDeniesDelegatedAndResourcePermissions(): void {
		$identity = new \Base3\Test\Authorization\PermissionUsermanagerStub();
		$identity->permissions = [\Base3\Usermanager\Permission::for('system', 'admin')];
		$s = new \Base3\Authorization\Base3System\Base3SystemAuthorizationService($identity);
		$other = new \Base3\Authorization\Dto\AuthorizationRequest('other', \Base3\Usermanager\Permission::for('catalog', 'edit'));
		$this->assertSame('unknown_authority', $s->decide($other)->reasonCode);
		$subject = new \Base3\Authorization\Dto\SubjectReference('base3', 'user', 1);
		$this->assertSame('unsupported_principal', $s->decide($this->request(null, $subject))->reasonCode);
		$this->assertSame('unsupported_principal', $s->decide($this->request(null, null, $subject))->reasonCode);
		$resource = new \Base3\Authorization\Dto\ResourceReference('base3', 'course', 5);
		$this->assertSame('resource_authorization_not_supported', $s->decide($this->request($resource))->reasonCode);
	}

	public function testBatchPreservesKeysAndRejectsNonRequests(): void {
		$s = new \Base3\Authorization\Base3System\Base3SystemAuthorizationService(new \Base3\Test\Authorization\PermissionUsermanagerStub());
		$this->assertSame(['second', 'first'], array_keys($s->decideMany(['second' => $this->request(), 'first' => $this->request()])));
		$this->expectException(\InvalidArgumentException::class);
		$s->decideMany([null]);
	}

}

require_once __DIR__ . '/../PermissionUsermanagerStub.php';
