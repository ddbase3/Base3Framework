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

use PHPUnit\Framework\TestCase;

final class RoleModelTest extends TestCase {

	public function testFromArrayCopiesOnlyKnownFields(): void {
		$value = \Base3\CoreFoundation\Usermanager\Role::fromArray(['id' => 17, 'name' => 'name-value', 'label' => 'label-value', 'info' => 'info-value', 'archive' => 0, 'permissions' => ['read'], 'unexpected' => 'ignored']);
		$this->assertSame(17, $value->id);
		$this->assertSame('name-value', $value->name);
		$this->assertSame('label-value', $value->label);
		$this->assertSame('info-value', $value->info);
		$this->assertSame(0, $value->archive);
		$this->assertSame(['read'], $value->permissions);
		$this->assertFalse(property_exists($value, 'unexpected'));
	}

	public function testNamedFactorySetsOnlyRoleName(): void {
		$role = \Base3\CoreFoundation\Usermanager\Role::named('editor');
		$this->assertSame('editor', $role->name);
		$this->assertSame([], $role->permissions);
	}

}
