<?php declare(strict_types=1);

namespace Base3\Diagnostics\Scope;

use Base3\Diagnostics\Api\IDiagnosticScope;

/** Default Privacy diagnostic category. */
final class PrivacyDiagnosticScope implements IDiagnosticScope {

	public static function getName(): string {
		return 'privacydiagnosticscope';
	}

	public function getPriority(): int {
		return 10;
	}

	public function getDisplayName(): string {
		return 'diagnosticscopedisplay';
	}

}
