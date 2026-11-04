<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Usermanager\Base3System\Base3SystemUsermanager;
use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;
use PHPUnit\Framework\TestCase;

final class Base3SystemGrantMutationCoverageTest extends TestCase {

	private function withManager(RecordingUsermanagerDatabaseFixture $database, callable $run): void {
		$previous = ServiceLocator::getInstance();
		try {
			$container = new ServiceLocator();
			$container->set('database', $database, IContainer::SHARED);
			$container->set('accesscontrol', new FixedIdentityAccesscontrolFixture(null), IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$run(new Base3SystemUsermanager());
		} finally { ServiceLocator::useInstance($previous); }
	}

	private function role(int $id = 3): Role {
		return Role::fromArray(['id' => $id, 'name' => 'editor']);
	}

	private function permission(int $id = 5): Permission {
		return Permission::fromArray(['id' => $id, 'scope' => 'page', 'permission' => 'write']);
	}

	public function testAssignRoleToUserResolvesBothNaturalKeys(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['id' => 7], ['id' => 3]];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->assignRoleToUser('alice', Role::named('editor')));
		});
		$this->assertStringContainsString("WHERE `name` = 'alice'", $db->queries[0]);
		$this->assertStringContainsString("WHERE `name` = 'editor'", $db->queries[1]);
		$this->assertStringContainsString('INSERT IGNORE INTO `base3system_sysuserrole`', $db->queries[2]);
		$this->assertStringContainsString('VALUES (7, 3)', $db->queries[2]);
	}

	public function testRevokeRoleFromUserUsesResolvedNumericId(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['id' => 7]];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->revokeRoleFromUser(7, $this->role()));
		});
		$this->assertStringContainsString('WHERE `id` = 7', $db->queries[0]);
		$this->assertStringContainsString('DELETE FROM `base3system_sysuserrole`', $db->queries[1]);
	}

	public function testMissingUserIdentifierRefusesGrantWithoutQuery(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->assignRoleToUser(null, $this->role()));
			$this->assertFalse($manager->revokeRoleFromUser('', $this->role()));
		});
		$this->assertSame([], $db->queries);
	}

	public function testUnknownUserCannotReceiveRole(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [null];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->assignRoleToUser('missing', $this->role()));
		});
		$this->assertSame(1, count($db->queries));
	}

	public function testUnknownRoleCannotBeAssignedToExistingUser(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['id' => 7], null];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->assignRoleToUser('alice', Role::named('missing')));
		});
		$this->assertSame(2, count($db->queries));
	}

	public function testAssignRoleToGroupResolvesNameAndWritesToGroupRoleTable(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['id' => 8]];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->assignRoleToGroup('authors', $this->role()));
		});
		$this->assertStringContainsString("WHERE `name` = 'authors'", $db->queries[0]);
		$this->assertStringContainsString('INSERT IGNORE INTO `base3system_sysgrouprole`', $db->queries[1]);
		$this->assertStringContainsString('VALUES (8, 3)', $db->queries[1]);
	}

	public function testRevokeRoleFromGroupUsesResolvedId(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['id' => 8]];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->revokeRoleFromGroup(8, $this->role()));
		});
		$this->assertStringContainsString('DELETE FROM `base3system_sysgrouprole`', $db->queries[1]);
	}

	public function testInvalidGroupIdRefusesGroupMutation(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->assignRoleToGroup(null, $this->role()));
			$this->assertFalse($manager->revokeRoleFromGroup('', $this->role()));
		});
		$this->assertSame([], $db->queries);
	}

	public function testAddPermissionToRoleByNumericIds(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->addPermissionToRole($this->role(), $this->permission()));
		});
		$this->assertStringContainsString('INSERT IGNORE INTO `base3system_sysrolepermission`', $db->queries[0]);
		$this->assertStringContainsString('VALUES (3, 5)', $db->queries[0]);
	}

	public function testRemovePermissionFromRoleByNumericIds(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->removePermissionFromRole($this->role(), $this->permission()));
		});
		$this->assertStringContainsString('DELETE FROM `base3system_sysrolepermission`', $db->queries[0]);
	}

	public function testResolvePermissionByScopeAndName(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [['id' => 5]];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertTrue($manager->addPermissionToRole($this->role(), Permission::for('page', 'write')));
		});
		$this->assertStringContainsString("`scope` = 'page'", $db->queries[0]);
		$this->assertStringContainsString("`permission` = 'write'", $db->queries[0]);
		$this->assertStringContainsString('INSERT IGNORE', $db->queries[1]);
	}

	public function testMissingPermissionDefinitionDoesNotWrite(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->addPermissionToRole($this->role(), Permission::for('', '')));
			$this->assertFalse($manager->removePermissionFromRole($this->role(), Permission::for('', '')));
		});
		$this->assertSame([], $db->queries);
	}

	public function testWriteErrorsAreReturnedAsFailureRatherThanSuccess(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->error = true;
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->addPermissionToRole($this->role(), $this->permission()));
		});
		$this->assertCount(1, $db->queries);
	}

	public function testUnknownPermissionLookupCannotCreateGrant(): void {
		$db = new RecordingUsermanagerDatabaseFixture();
		$db->singleRows = [null];
		$this->withManager($db, function(Base3SystemUsermanager $manager): void {
			$this->assertFalse($manager->addPermissionToRole($this->role(), Permission::for('missing', 'grant')));
		});
		$this->assertSame(1, count($db->queries));
	}
}

require_once __DIR__ . '/RecordingUsermanagerDatabaseFixture.php';
require_once __DIR__ . '/FixedIdentityAccesscontrolFixture.php';
