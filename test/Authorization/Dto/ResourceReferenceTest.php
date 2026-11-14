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

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class ResourceReferenceTest extends TestCase {

	public function testPreservesAuthorityTypeAndNativeIdentifier(): void {
		$resource = new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('ilias:one', 'course', 0);
		$this->assertSame('ilias:one', $resource->authority);
		$this->assertSame('course', $resource->type);
		$this->assertSame(0, $resource->id);
	}

	public function testRejectsEmptyAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\ResourceReference(' ', 'course', 1);
	}

	public function testRejectsEmptyType(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('native', '', 1);
	}

	public function testRejectsEmptyIdentifier(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\ResourceReference('native', 'course', '  ');
	}

}
