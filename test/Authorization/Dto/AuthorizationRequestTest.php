<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class AuthorizationRequestTest extends TestCase {

	public function testClonesPermissionAndKeepsTrustedContext(): void {
		$permission = \Base3\Usermanager\Permission::for('course', 'read');
		$resource = new \Base3\Authorization\Dto\ResourceReference('host', 'course', 17);
		$subject = new \Base3\Authorization\Dto\SubjectReference('host', 'user', 'joe');
		$request = new \Base3\Authorization\Dto\AuthorizationRequest('host', $permission, $resource, $subject, ['limit' => 3], ['source' => 'server']);
		$permission->permission = 'delete';
		$this->assertSame('read', $request->permission->permission);
		$this->assertNotSame($permission, $request->permission);
		$this->assertSame($subject, $request->subject);
		$this->assertSame(['limit' => 3], $request->parameters);
		$this->assertSame(['source' => 'server'], $request->context);
	}

	public function testRejectsResourceFromOtherAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\AuthorizationRequest('host', \Base3\Usermanager\Permission::for('course', 'read'), new \Base3\Authorization\Dto\ResourceReference('other', 'course', 1));
	}

	public function testRejectsBlankAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\AuthorizationRequest(' ', \Base3\Usermanager\Permission::for('course', 'read'));
	}

	public function testRejectsMissingScope(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\AuthorizationRequest('host', \Base3\Usermanager\Permission::for(' ', 'read'));
	}

	public function testRejectsMissingPermission(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\AuthorizationRequest('host', \Base3\Usermanager\Permission::for('course', ''));
	}

}
