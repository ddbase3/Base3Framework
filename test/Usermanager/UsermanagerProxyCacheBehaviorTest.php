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

namespace Base3\Test\Usermanager;

use Base3\CoreFoundation\Usermanager\Group;
use Base3\CoreFoundation\Usermanager\Permission;
use Base3\CoreFoundation\Usermanager\Role;
use Base3\CoreFoundation\Usermanager\User;
use Base3\Core\Usermanager\UsermanagerProxy;
use PHPUnit\Framework\TestCase;

final class UsermanagerProxyCacheBehaviorTest extends TestCase {

	public function testCurrentUserIsHydratedAndCached(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->responses['getUser'] = ['id' => '42', 'userid' => 'jane'];
		$proxy = new UsermanagerProxy($connector);
		$user = $proxy->getUser();
		$this->assertInstanceOf(User::class, $user);
		$this->assertSame('42', $user->id);
		$this->assertSame($user, $proxy->getUser());
		$this->assertSame(1, $connector->countCalls('getUser'));
	}

	public function testNullCurrentUserIsNotCached(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$proxy = new UsermanagerProxy($connector);
		$this->assertNull($proxy->getUser());
		$this->assertNull($proxy->getUser());
		$this->assertSame(2, $connector->countCalls('getUser'));
	}

	public function testLookupByIdCachesMissingAndExistingUsersSeparately(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->usersById['42'] = ['id' => '42', 'userid' => 'jane'];
		$proxy = new UsermanagerProxy($connector);
		$known = $proxy->getUserById(42);
		$this->assertInstanceOf(User::class, $known);
		$this->assertSame($known, $proxy->getUserById('42'));
		$this->assertNull($proxy->getUserById(100));
		$this->assertNull($proxy->getUserById('100'));
		$this->assertSame(2, $connector->countCalls('getUserById'));
	}

	public function testMembershipListsHydrateAndCacheConnectorData(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->responses['getGroups'] = [['id' => 2, 'name' => 'group']];
		$connector->responses['getRoles'] = [['id' => 3, 'name' => 'editor']];
		$connector->responses['getPermissions'] = [['id' => 4, 'scope' => 'catalog', 'permission' => 'read']];
		$proxy = new UsermanagerProxy($connector);
		$this->assertInstanceOf(Group::class, $proxy->getGroups()[0]);
		$this->assertInstanceOf(Role::class, $proxy->getRoles()[0]);
		$this->assertInstanceOf(Permission::class, $proxy->getPermissions()[0]);
		$this->assertSame('editor', $proxy->getRoles()[0]->name);
		$proxy->getGroups(); $proxy->getPermissions();
		$this->assertSame(1, $connector->countCalls('getGroups'));
		$this->assertSame(1, $connector->countCalls('getRoles'));
		$this->assertSame(1, $connector->countCalls('getPermissions'));
	}

	public function testAlreadyHydratedMembershipObjectsKeepTheirIdentity(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$role = Role::named('admin');
		$connector->responses['getRoles'] = [$role];
		$proxy = new UsermanagerProxy($connector);
		$this->assertSame($role, $proxy->getRoles()[0]);
		$this->assertSame(1, $connector->countCalls('getRoles'));
	}

	public function testCatalogListsHydrateAndAreCachedIndependently(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->responses['getAllUsers'] = [['id' => 1, 'userid' => 'one']];
		$connector->responses['getAllGroups'] = [['id' => 2, 'name' => 'group']];
		$connector->responses['getAllRoles'] = [['id' => 3, 'name' => 'role']];
		$connector->responses['getAllPermissions'] = [['id' => 4, 'scope' => 'system', 'permission' => 'view']];
		$proxy = new UsermanagerProxy($connector);
		$this->assertInstanceOf(User::class, $proxy->getAllUsers()[0]);
		$this->assertInstanceOf(Group::class, $proxy->getAllGroups()[0]);
		$this->assertInstanceOf(Role::class, $proxy->getAllRoles()[0]);
		$this->assertInstanceOf(Permission::class, $proxy->getAllPermissions()[0]);
		$proxy->getAllUsers(); $proxy->getAllGroups(); $proxy->getAllRoles(); $proxy->getAllPermissions();
		foreach (['getAllUsers', 'getAllGroups', 'getAllRoles', 'getAllPermissions'] as $operation) {
			$this->assertSame(1, $connector->countCalls($operation));
		}
	}

	public function testRoleAssignmentInvalidatesUserAndAuthorizationCachesButNotGroups(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->responses['getUser'] = ['id' => 1];
		$connector->responses['getGroups'] = [];
		$connector->responses['getRoles'] = [];
		$connector->responses['getPermissions'] = [];
		$connector->responses['getAllRoles'] = [];
		$connector->responses['getAllGroups'] = [];
		$connector->responses['getAllUsers'] = [];
		$connector->responses['assignRoleToUser'] = true;
		$proxy = new UsermanagerProxy($connector);
		$proxy->getUser(); $proxy->getGroups(); $proxy->getRoles(); $proxy->getPermissions();
		$proxy->getAllRoles(); $proxy->getAllGroups(); $proxy->getAllUsers();
		$this->assertTrue($proxy->assignRoleToUser('1', Role::named('editor')));
		$proxy->getUser(); $proxy->getGroups(); $proxy->getRoles(); $proxy->getPermissions();
		$proxy->getAllRoles(); $proxy->getAllGroups(); $proxy->getAllUsers();
		$this->assertSame(2, $connector->countCalls('getUser'));
		$this->assertSame(1, $connector->countCalls('getGroups'));
		$this->assertSame(2, $connector->countCalls('getRoles'));
		$this->assertSame(2, $connector->countCalls('getPermissions'));
		$this->assertSame(2, $connector->countCalls('getAllRoles'));
		$this->assertSame(1, $connector->countCalls('getAllGroups'));
		$this->assertSame(2, $connector->countCalls('getAllUsers'));
	}

	public function testFailedRegistrationPreservesUserCacheAndSuccessfulRegistrationClearsIt(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->responses['getUser'] = ['id' => 1];
		$proxy = new UsermanagerProxy($connector);
		$proxy->getUser();
		$connector->responses['registUser'] = false;
		$this->assertFalse($proxy->registUser('new', 'secret'));
		$proxy->getUser();
		$this->assertSame(1, $connector->countCalls('getUser'));
		$connector->responses['registUser'] = true;
		$this->assertTrue($proxy->registUser('new', 'secret', ['name' => 'New']));
		$proxy->getUser();
		$this->assertSame(2, $connector->countCalls('getUser'));
		$this->assertSame(['new', 'secret', ['name' => 'New']], $connector->calls[count($connector->calls) - 2][1]);
	}

	public function testRoleAndPermissionMutationsDelegateAndConvertResults(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$role = Role::named('editor');
		$permission = Permission::for('system', 'view');
		$connector->responses['assignRoleToUser'] = 1;
		$connector->responses['revokeRoleFromUser'] = false;
		$connector->responses['assignRoleToGroup'] = 1;
		$connector->responses['revokeRoleFromGroup'] = false;
		$connector->responses['addPermissionToRole'] = 1;
		$connector->responses['removePermissionFromRole'] = false;
		$proxy = new UsermanagerProxy($connector);
		$this->assertTrue($proxy->assignRoleToUser('42', $role));
		$this->assertFalse($proxy->revokeRoleFromUser('42', $role));
		$this->assertTrue($proxy->assignRoleToGroup('10', $role));
		$this->assertFalse($proxy->revokeRoleFromGroup('10', $role));
		$this->assertTrue($proxy->addPermissionToRole($role, $permission));
		$this->assertFalse($proxy->removePermissionFromRole($role, $permission));
		$this->assertSame(['assignRoleToUser', ['42', $role]], $connector->calls[0]);
		$this->assertSame(['removePermissionFromRole', [$role, $permission]], $connector->calls[5]);
	}

	public function testPasswordChangeAndRoleMembershipRemainBackendOwned(): void {
		$connector = new ProxyRecordingConnectorFixture();
		$connector->responses['hasRole'] = true;
		$connector->responses['changePassword'] = false;
		$role = Role::named('administrator');
		$proxy = new UsermanagerProxy($connector);
		$this->assertTrue($proxy->hasRole($role));
		$this->assertFalse($proxy->changePassword('old', 'new'));
		$this->assertSame(['hasRole', [$role]], $connector->calls[0]);
		$this->assertSame(['changePassword', ['old', 'new']], $connector->calls[1]);
	}
}

require_once __DIR__ . '/ProxyRecordingConnectorFixture.php';
