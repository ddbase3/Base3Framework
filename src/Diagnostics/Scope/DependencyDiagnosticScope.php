<?php declare(strict_types=1);

namespace Base3\Diagnostics\Scope;

use Base3\Diagnostics\Api\IDiagnosticScope;

/** Default Dependency diagnostic category. */
final class DependencyDiagnosticScope implements IDiagnosticScope {

	public static function getName(): string {
		return 'dependencydiagnosticscope';
	}

	public function getPriority(): int {
		return 30;
	}

	public function getDisplayName(): string {
		return 'diagnosticscopedisplay';
	}

}
