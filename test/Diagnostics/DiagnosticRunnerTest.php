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

namespace Base3\Test\Diagnostics;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticScope;
use Base3\Core\Diagnostics\DiagnosticRunner;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Core\Diagnostics\Scope\PrivacyDiagnosticScope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DiagnosticRunnerTest extends TestCase {

	public function testListsScopesAndChecksWithoutExecutingThem(): void {
		$check = new DiagnosticRunnerTestCheck();
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstancesByInterface')->willReturnCallback(
			static fn(string $interface): array => match($interface) {
				IDiagnosticScope::class => [new PrivacyDiagnosticScope()],
				IDiagnosticCheck::class => [$check],
				default => []
			}
		);
		$classmap->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $interface, string $name) => $interface === IDiagnosticScope::class ? new PrivacyDiagnosticScope() : $check
		);

		$runner = new DiagnosticRunner($classmap);
		$this->assertCount(1, $runner->getScopes());
		$this->assertInstanceOf(PrivacyDiagnosticScope::class, $runner->getScopes()[0]);
		$this->assertSame([$check], $runner->getChecks(PrivacyDiagnosticScope::getName()));
		$this->assertSame(0, $check->executions);
	}

	public function testExceptionsBecomeControlledErrorResults(): void {
		$check = new DiagnosticRunnerTestCheck();
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $interface, string $name) => $interface === IDiagnosticScope::class ? new PrivacyDiagnosticScope() : $check
		);
		$runner = new DiagnosticRunner($classmap);
		$result = $runner->run(PrivacyDiagnosticScope::getName(), DiagnosticRunnerTestCheck::getName());
		$this->assertSame(DiagnosticCheckResult::ERROR, $result->status);
		$this->assertSame('error', $result->messageKey);
		$this->assertSame(1, $check->executions);
	}

	public function testRejectsWrongScopeBeforeExecutingTheCheck(): void {
		$check = new DiagnosticRunnerTestCheck();
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $interface, string $name) => $interface === IDiagnosticScope::class ? new PrivacyDiagnosticScope() : $check
		);
		$this->expectException(InvalidArgumentException::class);
		(new DiagnosticRunner($classmap))->run(PrivacyDiagnosticScope::getName(), 'notregistered');
	}

}

require_once __DIR__ . '/DiagnosticRunnerTestCheck.php';
