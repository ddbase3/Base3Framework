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

final class DiagnosticRunnerTestCheck implements IDiagnosticCheck {

	public int $executions = 0;

	public static function getName(): string { return 'diagnosticrunnertestcheck'; }
	public function getScope(): string { return PrivacyDiagnosticScope::getName(); }
	public function run(): DiagnosticCheckResult {
		$this->executions++;
		throw new RuntimeException('Potential sensitive exception detail');
	}

}
