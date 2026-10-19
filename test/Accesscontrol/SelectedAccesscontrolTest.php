<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol;

use Base3\Accesscontrol\Api\IAuthentication;
use Base3\Accesscontrol\Selected\SelectedAccesscontrol;
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
