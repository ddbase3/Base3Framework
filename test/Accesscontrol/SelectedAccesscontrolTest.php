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

namespace Base3\Test\Accesscontrol;

use Base3\CoreFoundation\Accesscontrol\Api\IAuthentication;
use Base3\Core\Accesscontrol\Selected\SelectedAccesscontrol;
use PHPUnit\Framework\TestCase;

final class SelectedAccesscontrolTest extends TestCase {

	public function testEmbeddedIdentityLookupRunsConfiguredAuthenticationOnce(): void {
		$auth = $this->createMock(IAuthentication::class);
		$auth->expects(self::once())->method('login')->willReturn(42);
		$auth->expects(self::once())->method('logout');
		$auth->expects(self::once())->method('keep')->with(42);
		$auth->expects(self::once())->method('finish')->with(42);

		$accesscontrol = new SelectedAccesscontrol([$auth]);
		self::assertSame(42, $accesscontrol->getUserId());
		self::assertSame(42, $accesscontrol->getUserId());
		$accesscontrol->authenticate();
		self::assertSame(42, $accesscontrol->getUserId());
	}

	public function testExplicitMiddlewareAuthenticationUsesSameResolvedIdentity(): void {
		$auth = $this->createMock(IAuthentication::class);
		$auth->expects(self::once())->method('login')->willReturn(27);

		$accesscontrol = new SelectedAccesscontrol([$auth]);
		$accesscontrol->authenticate();
		self::assertSame(27, $accesscontrol->getUserId());
	}

	public function testEmbeddedAnonymousLookupDoesNotGrantIdentity(): void {
		$auth = $this->createMock(IAuthentication::class);
		$auth->expects(self::once())->method('login')->willReturn(null);

		$accesscontrol = new SelectedAccesscontrol([$auth]);
		self::assertNull($accesscontrol->getUserId());
		self::assertNull($accesscontrol->getUserId());
	}
}
