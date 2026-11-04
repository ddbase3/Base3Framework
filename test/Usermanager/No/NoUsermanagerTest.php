<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\No;

use PHPUnit\Framework\TestCase;

final class NoUsermanagerTest extends TestCase {

	public function testDeniesAllAuthorizationAndReturnsNoIdentityOrCatalogues(): void {
		$m=new \Base3\Usermanager\No\NoUsermanager();
		$this->assertNull($m->getUser());
		$this->assertNull($m->getUserById(4));
		$this->assertSame([], $m->getGroups());
		$this->assertSame([], $m->getRoles());
		$this->assertSame([], $m->getPermissions());
		$this->assertSame([], $m->getAllUsers());
		$this->assertSame([], $m->getAllGroups());
		$this->assertSame([], $m->getAllRoles());
		$this->assertSame([], $m->getAllPermissions());
		$this->assertSame([], $m->searchUsers('any'));
		$this->assertFalse($m->hasRole(\Base3\Usermanager\Role::named('admin')));
	}

	public function testAllMutationRequestsAreRejected(): void {
		$m=new \Base3\Usermanager\No\NoUsermanager();
		$role=\Base3\Usermanager\Role::named('reader');
		$permission=\Base3\Usermanager\Permission::for('data','read');
		$this->assertFalse($m->registUser('u','pw'));
		$this->assertFalse($m->changePassword('old','new'));
		$this->assertFalse($m->assignRoleToUser('u',$role));
		$this->assertFalse($m->revokeRoleFromUser('u',$role));
		$this->assertFalse($m->assignRoleToGroup('g',$role));
		$this->assertFalse($m->revokeRoleFromGroup('g',$role));
		$this->assertFalse($m->addPermissionToRole($role,$permission));
		$this->assertFalse($m->removePermissionFromRole($role,$permission));
	}

	public function testSearchValidatesBounds(): void {
		$m=new \Base3\Usermanager\No\NoUsermanager();
		$this->assertSame([],$m->searchUsers('x',1,0));
		$this->assertSame([],$m->searchUsers('x',100,5));
		foreach ([[0,0],[101,0],[1,-1]] as [$limit,$offset]) {
			try { $m->searchUsers('x',$limit,$offset);$this->fail('Expected invalid user search bounds'); }
			catch (\InvalidArgumentException $e) { $this->assertStringContainsString('limit 1..100',$e->getMessage()); }
		}
	}

}
