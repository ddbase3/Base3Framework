<?php declare(strict_types=1);

namespace Base3\Test\Authorization\No;

use PHPUnit\Framework\TestCase;

final class NoAuthorizationServiceTest extends TestCase {

	public function testNeverAllowsOperationsWithoutNativeAuthority(): void {
		$s = new \Base3\Authorization\No\NoAuthorizationService();
		$this->assertSame('unconfigured', $s->getDefaultAuthority());
		$request = new \Base3\Authorization\Dto\AuthorizationRequest('host', \Base3\Usermanager\Permission::for('course', 'read'));
		$decision = $s->decide($request);
		$this->assertFalse($decision->isAllowed());
		$this->assertSame('no_authority_configured', $decision->reasonCode);
		$batch = $s->decideMany(['a' => $request]);
		$this->assertSame(['a'], array_keys($batch));
		$this->assertSame($decision->reasonCode, $batch['a']->reasonCode);
	}

	public function testBatchRejectsNonRequestObjects(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Authorization\No\NoAuthorizationService())->decideMany([false]);
	}

}
