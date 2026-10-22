<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics;

use Base3\Api\IClassMap;
use Base3\Diagnostics\Api\IDiagnosticCheck;
use Base3\Diagnostics\Api\IDiagnosticScope;
use Base3\Diagnostics\DiagnosticRunner;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Scope\PrivacyDiagnosticScope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DiagnosticRunnerTest extends TestCase {

	public function testListsScopesAndChecksWithoutExecutingThem(): void {
		$check = new DiagnosticRunnerTestCheck();
		$classmap = $this->createMock(IClassMap::class);
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
		$classmap = $this->createMock(IClassMap::class);
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
		$classmap = $this->createMock(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $interface, string $name) => $interface === IDiagnosticScope::class ? new PrivacyDiagnosticScope() : $check
		);
		$this->expectException(InvalidArgumentException::class);
		(new DiagnosticRunner($classmap))->run(PrivacyDiagnosticScope::getName(), 'notregistered');
	}

}

final class DiagnosticRunnerTestCheck implements IDiagnosticCheck {

	public int $executions = 0;

	public static function getName(): string { return 'diagnosticrunnertestcheck'; }
	public function getScope(): string { return PrivacyDiagnosticScope::getName(); }
	public function run(): DiagnosticCheckResult {
		$this->executions++;
		throw new RuntimeException('Potential sensitive exception detail');
	}

}
