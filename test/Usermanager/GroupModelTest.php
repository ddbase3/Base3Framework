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

final class GroupModelTest extends TestCase {

	public function testFromArrayCopiesOnlyKnownFields(): void {
		$value = \Base3\CoreFoundation\Usermanager\Group::fromArray(['id' => 17, 'name' => 'name-value', 'info' => 'info-value', 'archive' => 0, 'roles' => ['admin'], 'unexpected' => 'ignored']);
		$this->assertSame(17, $value->id);
		$this->assertSame('name-value', $value->name);
		$this->assertSame('info-value', $value->info);
		$this->assertSame(0, $value->archive);
		$this->assertSame(['admin'], $value->roles);
		$this->assertFalse(property_exists($value, 'unexpected'));
	}

}
