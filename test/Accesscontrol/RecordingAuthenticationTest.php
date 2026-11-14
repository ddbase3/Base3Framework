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

use PHPUnit\Framework\TestCase;

final class RecordingAuthenticationTest extends TestCase {

	public function testRequestAuthenticationKeepsRecordedIdentity(): void {
		$authentication = new RecordingAuthentication('member');
		self::assertSame('member', $authentication->login());
		self::assertSame(['login'], $authentication->lifecycle);
	}

	public function testExplicitCredentialsCannotSubstituteRecordedIdentity(): void {
		$authentication = new RecordingAuthentication('member');
		self::assertNull($authentication->login(['username' => 'other', 'password' => 'secret']));
		self::assertNull($authentication->login([]));
		self::assertSame(['login', 'login'], $authentication->lifecycle);
	}
}

require_once __DIR__ . '/RecordingAuthentication.php';
