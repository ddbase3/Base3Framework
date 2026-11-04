<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Scope;

use PHPUnit\Framework\TestCase;

final class PrivacyDiagnosticScopeTest extends TestCase {

	public function testPrivacyCategoryIsDiscoverableAndStable(): void {
		$s=new \Base3\Diagnostics\Scope\PrivacyDiagnosticScope();
		$this->assertSame('privacydiagnosticscope',$s::getName());
		$this->assertSame('diagnosticscopedisplay',$s->getDisplayName());
		$this->assertSame(10,$s->getPriority());
	}

}
