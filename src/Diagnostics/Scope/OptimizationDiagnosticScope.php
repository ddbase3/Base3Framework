<?php declare(strict_types=1);

namespace Base3\Diagnostics\Scope;

use Base3\Diagnostics\Api\IDiagnosticScope;

/** Default Optimization diagnostic category. */
final class OptimizationDiagnosticScope implements IDiagnosticScope {

	public static function getName(): string {
		return 'optimizationdiagnosticscope';
	}

	public function getPriority(): int {
		return 40;
	}

	public function getDisplayName(): string {
		return 'diagnosticscopedisplay';
	}

}
