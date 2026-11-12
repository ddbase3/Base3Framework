<?php declare(strict_types=1);

namespace Base3\Diagnostics\Check;

use Base3\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Scope\InstallationDiagnosticScope;

/** Checks required repository-root documents for all inspectable BASE3 modules. */
final class RequiredModuleFilesDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	private const REQUIRED_FILES = ['README.md', 'PRIVACY.md', 'LICENSE'];

	public static function getName(): string {
		return 'requiredmodulefilesdiagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$inspected = 0;

		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = basename($root) . ': cannot inspect required files without a valid base3.json';
				continue;
			}
			$inspected++;
			foreach(self::REQUIRED_FILES as $filename) {
				$file = $root . '/' . $filename;
				if(!is_file($file) || is_link($file)) {
					$failures[] = basename($root) . ': missing ' . $filename;
				} elseif(!is_readable($file)) {
					$failures[] = basename($root) . ': unreadable ' . $filename;
				}
			}
		}

		return $this->report($failures, $warnings, $inspected);
	}

}
