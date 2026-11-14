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

final class AuthorizationDecisionTest extends TestCase {

	public function testFactoryOutcomesAndDefaultReasons(): void {
		$allow = \Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision::allow();
		$this->assertSame('allow', $allow->outcome);
		$this->assertSame('authorized', $allow->reasonCode);
		$this->assertTrue($allow->isAllowed());
		$deny = \Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision::deny();
		$this->assertSame('not_authorized', $deny->reasonCode);
		$this->assertFalse($deny->isAllowed());
		$unknown = \Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision::indeterminate();
		$this->assertSame('authority_unavailable', $unknown->reasonCode);
		$this->assertFalse($unknown->isAllowed());
	}

	public function testCustomReasonsAndInvalidOutcome(): void {
		$this->assertSame('native_error', \Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision::indeterminate('native_error')->reasonCode);
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision('unknown');
	}

}
