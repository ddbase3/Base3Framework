<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class AuthorizationDecisionTest extends TestCase {

	public function testFactoryOutcomesAndDefaultReasons(): void {
		$allow = \Base3\Authorization\Dto\AuthorizationDecision::allow();
		$this->assertSame('allow', $allow->outcome);
		$this->assertSame('authorized', $allow->reasonCode);
		$this->assertTrue($allow->isAllowed());
		$deny = \Base3\Authorization\Dto\AuthorizationDecision::deny();
		$this->assertSame('not_authorized', $deny->reasonCode);
		$this->assertFalse($deny->isAllowed());
		$unknown = \Base3\Authorization\Dto\AuthorizationDecision::indeterminate();
		$this->assertSame('authority_unavailable', $unknown->reasonCode);
		$this->assertFalse($unknown->isAllowed());
	}

	public function testCustomReasonsAndInvalidOutcome(): void {
		$this->assertSame('native_error', \Base3\Authorization\Dto\AuthorizationDecision::indeterminate('native_error')->reasonCode);
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\Dto\AuthorizationDecision('unknown');
	}

}
