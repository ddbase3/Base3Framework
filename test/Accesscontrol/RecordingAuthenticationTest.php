<?php declare(strict_types=1);

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
