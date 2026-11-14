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

final class AuthorizationCapabilitiesTest extends TestCase {

	public function testPreservesNativeAdministrationCapabilities(): void {
		$c = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationCapabilities('ilias:one', true, false, true, ['user', 'role'], ['readonly']);
		$this->assertSame('ilias:one', $c->authority);
		$this->assertTrue($c->canRead);
		$this->assertFalse($c->canWrite);
		$this->assertTrue($c->supportsHierarchy);
		$this->assertSame(['user', 'role'], $c->subjectKinds);
		$this->assertSame(['readonly'], $c->limitations);
	}

	public function testLimitationsAreOptional(): void {
		$c = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationCapabilities('host', false, false, false, []);
		$this->assertSame([], $c->limitations);
	}

}
