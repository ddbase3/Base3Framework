<?php declare(strict_types=1);

namespace Base3\Diagnostics\Scope;

use Base3\Diagnostics\Api\IDiagnosticScope;

/** Default Installation diagnostic category. */
final class InstallationDiagnosticScope implements IDiagnosticScope {

	public static function getName(): string {
		return 'installationdiagnosticscope';
	}

	public function getPriority(): int {
		return 20;
	}

	public function getDisplayName(): string {
		return 'diagnosticscopedisplay';
	}

}
