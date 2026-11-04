<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Usermanager\Base3System\Base3SystemUsermanager;
use Base3\Usermanager\Role;
use PHPUnit\Framework\TestCase;

final class Base3SystemIdentityCoverageTest extends TestCase {

	private function withIdentity(mixed $identity, RecordingUsermanagerDatabaseFixture $database, callable $run): void {
		$previous = ServiceLocator::getInstance();
		try {
			$container = new ServiceLocator();
			$container->set('database', $database, IContainer::SHARED);
			$container->set('accesscontrol', new FixedIdentityAccesscontrolFixture($identity), IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$run(new Base3SystemUsermanager());
		} finally { ServiceLocator::useInstance($previous); }
	}

	private function userRow(): array {
		return ['id' => 7, 'userid' => 'alice', 'fullname' => 'Alice', 'email' => 'a@example.test', 'mode' => 1, 'lang' => 'de'];
	}

	public function testAnonymousUserHasNoIdentityGroupsRolesOrPermissions(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			$this->assertNull($manager->getUser());
			$this->assertSame([], $manager->getGroups());
			$this->assertSame([], $manager->getRoles());
			$this->assertSame([], $manager->getPermissions());
			$this->assertFalse($manager->hasRole(Role::named('admin')));
			$this->assertSame(['depending_services' => 'Ok'], $manager->checkDependencies());
		});
		$this->assertSame([], $db->queries);
	}

	public function testInternalUserGetsOnlyDocumentedInternalIdentity(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withIdentity('internal', $db, function(Base3SystemUsermanager $manager): void {
			$user = $manager->getUser();
			$this->assertSame('internal', $user->id);
			$this->assertSame('internal', $user->userid);
			$this->assertSame('admin', $user->role);
			$this->assertSame('admin', $user->roles[0]->name);
			$this->assertSame($user, $manager->getUser());
			$this->assertSame('internal', $manager->getUserById('internal')->id);
			$this->assertSame([], $manager->getGroups());
			$this->assertTrue($manager->hasRole(Role::named('admin')));
			$this->assertFalse($manager->hasRole(Role::named('member')));
			$this->assertSame('admin', $manager->getPermissions()[0]->permission);
		});
		$this->assertSame([], $db->queries);
	}

	public function testMissingCurrentUserRowDoesNotInventIdentity(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [null];
		$this->withIdentity('unknown', $db, function(Base3SystemUsermanager $manager): void {
			$this->assertNull($manager->getUser());
			$this->assertSame([], $manager->getGroups());
		});
		$this->assertSame(2, count($db->queries));
	}

	public function testExistingCurrentUserGetsRoleMetadataAndIsCached(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [$this->userRow()];
		$db->multiRows = [[['id' => 9, 'name' => 'admin', 'archive' => 0]]];
		$this->withIdentity('alice', $db, function(Base3SystemUsermanager $manager): void {
			$user = $manager->getUser();
			$this->assertSame('alice', $user->userid);
			$this->assertSame('Alice', $user->name);
			$this->assertSame('admin', $user->role);
			$this->assertSame('admin', $user->roles[0]->name);
			$this->assertSame($user, $manager->getUser());
			$this->assertSame($user, $manager->getUserById(7));
			$this->assertTrue($manager->hasRole(Role::named('admin')));
		});
		$this->assertSame(2, count($db->queries));
	}

	public function testLookupByIdRejectsUntrustedOrNonpositiveValuesWithoutQuery(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			foreach (['not-id', '', 0, -3, '0', '0.0'] as $id) {
				$this->assertNull($manager->getUserById($id));
			}
		});
		$this->assertSame([], $db->queries);
	}

	public function testLookupByPositiveIdLoadsUserAndEffectiveRole(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [$this->userRow()];
		$db->multiRows = [[['id' => 2, 'name' => 'member', 'archive' => 0]]];
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			$user = $manager->getUserById('7');
			$this->assertSame(7, $user->id);
			$this->assertSame('member', $user->role);
			$this->assertCount(1, $user->roles);
		});
		$this->assertStringContainsString('WHERE u.`id` = 7', $db->queries[0]);
	}

	public function testUnknownPositiveIdReturnsNull(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [null];
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			$this->assertNull($manager->getUserById(123));
		});
	}

	public function testGroupRoleAndPermissionCollectionsUseDatabaseRows(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [$this->userRow()];
		$db->multiRows = [
			[['id' => 4, 'name' => 'authors', 'info' => '', 'archive' => 0]],
			[['id' => 2, 'name' => 'member', 'archive' => 0]],
			[['id' => 5, 'scope' => 'project', 'permission' => 'read', 'archive' => 0]]
		];
		$this->withIdentity('alice', $db, function(Base3SystemUsermanager $manager): void {
			$this->assertSame('authors', $manager->getGroups()[0]->name);
			$this->assertSame('member', $manager->getRoles()[0]->name);
			$this->assertSame('read', $manager->getPermissions()[0]->permission);
			$this->assertTrue($manager->hasRole(Role::named('member')));
			$this->assertFalse($manager->hasRole(Role::named('admin')));
		});
		$this->assertSame(4, count($db->queries));
	}

	public function testRolelessUserHasNoPermissions(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [$this->userRow()];
		$db->multiRows = [[], []];
		$this->withIdentity('alice', $db, function(Base3SystemUsermanager $manager): void {
			$this->assertSame([], $manager->getRoles());
			$this->assertSame([], $manager->getPermissions());
		});
	}

	public function testCatalogMethodsReturnMappedRowsAndReuseNonemptyCache(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->multiRows = [
			[['id' => 2, 'name' => 'editors', 'archive' => 0]],
			[['id' => 3, 'name' => 'editor', 'archive' => 0]],
			[['id' => 4, 'scope' => 'page', 'permission' => 'write', 'archive' => 0]]
		];
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			$this->assertSame('editors', $manager->getAllGroups()[0]->name);
			$this->assertSame('editor', $manager->getAllRoles()[0]->name);
			$this->assertSame('write', $manager->getAllPermissions()[0]->permission);
			$this->assertSame('editors', $manager->getAllGroups()[0]->name);
			$this->assertSame('editor', $manager->getAllRoles()[0]->name);
			$this->assertSame('write', $manager->getAllPermissions()[0]->permission);
		});
		$this->assertSame(3, count($db->queries));
	}

	public function testAllUserListingHydratesUsersWithTheirEffectiveRoles(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->multiRows = [[$this->userRow()], [['id' => 3, 'name' => 'admin', 'archive' => 0]]];
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			$users = $manager->getAllUsers();
			$this->assertCount(1, $users);
			$this->assertSame('alice', $users[0]->userid);
			$this->assertSame('admin', $users[0]->role);
		});
		$this->assertSame(2, count($db->queries));
	}

	public function testGuestPasswordChangeDoesNotContactDatabase(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withIdentity(null, $db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->changePassword('old', 'new'));
		});
		$this->assertSame(0, $db->connections);
	}

	public function testRegisterUserAndAuthenticatedPasswordChangeRemainExplicitlyUnsupported(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withIdentity('alice', $db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->registUser('alice', 'secret'));
			$this->assertFalse($manager->changePassword('old', 'new'));
		});
		$this->assertSame(2, $db->connections);
	}
}

require_once __DIR__ . '/RecordingUsermanagerDatabaseFixture.php';
require_once __DIR__ . '/FixedIdentityAccesscontrolFixture.php';
