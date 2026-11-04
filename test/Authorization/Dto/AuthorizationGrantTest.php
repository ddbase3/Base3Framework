<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class AuthorizationGrantTest extends TestCase {

	public function testPreservesSubjectResourceAndInheritance(): void {
		$subject = new \Base3\Authorization\Dto\SubjectReference('host', 'user', 1);
		$resource = new \Base3\Authorization\Dto\ResourceReference('host', 'course', 2);
		$origin = new \Base3\Authorization\Dto\ResourceReference('host', 'category', 3);
		$permission = \Base3\Usermanager\Permission::for('course', 'read');
		$grant = new \Base3\Authorization\Dto\AuthorizationGrant($subject, $permission, $resource, false, true, $origin);
		$this->assertSame($subject, $grant->subject);
		$this->assertSame($resource, $grant->resource);
		$this->assertSame($origin, $grant->origin);
		$this->assertTrue($grant->inherited);
		$this->assertFalse($grant->editable);
		$this->assertSame('read', $grant->permission->permission);
	}

	public function testInheritanceIsUnknownByDefault(): void {
		$grant = new \Base3\Authorization\Dto\AuthorizationGrant(
			new \Base3\Authorization\Dto\SubjectReference('host', 'user', 1),
			\Base3\Usermanager\Permission::for('course', 'read'),
			new \Base3\Authorization\Dto\ResourceReference('host', 'course', 1),
			true
		);
		$this->assertNull($grant->inherited);
		$this->assertNull($grant->origin);
	}

}
