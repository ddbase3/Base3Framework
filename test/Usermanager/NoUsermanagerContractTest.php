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

use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\Core\Usermanager\No\NoUsermanager;
use Base3\CoreFoundation\Usermanager\Permission;
use Base3\CoreFoundation\Usermanager\Role;
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
