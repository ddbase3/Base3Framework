<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\No\NoUsermanager;
use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;
use PHPUnit\Framework\TestCase;

final class NoUsermanagerContractTest extends TestCase {

	public function testNoUsermanagerImplementsUpdatedUsermanagerContract(): void {
		$manager = new NoUsermanager();
		self::assertInstanceOf(IUsermanager::class, $manager);
		self::assertSame([], $manager->searchUsers('any'));
		self::assertFalse($manager->hasRole(Role::named('admin')));
		self::assertFalse($manager->addPermissionToRole(Role::named('admin'), Permission::for('system', 'admin')));
		self::assertFalse($manager->removePermissionFromRole(Role::named('admin'), Permission::for('system', 'admin')));
	}

	public function testNoUsermanagerRejectsInvalidPagination(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new NoUsermanager())->searchUsers('test', 0);
	}
}
