<?php declare(strict_types=1);

namespace Base3\Diagnostics\Check;

use Base3\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Inspection\ModuleSourceInventory;
use Base3\Diagnostics\Inspection\PhpSourceInspector;
use Base3\Diagnostics\Scope\InstallationDiagnosticScope;

/** Reports migration down() readiness and explicit legacy checksum exemptions without loading plugin code. */
final class MigrationReadinessDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'migrationreadinessdiagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$inspector = new PhpSourceInspector();
		$inspected = 0;

		foreach($roots['roots'] as $root) {
			$manifest = $inventory->manifest($root);
			if($manifest['errors']) {
				$failures[] = 'Cannot inspect migration sources in ' . basename($root) . ': invalid or missing manifest';
				continue;
			}
			foreach($inventory->sourceFiles($root) as $file) {
				$source = $inspector->inspect($file);
				$location = ModuleSourceInventory::relativeFile($root, $file);
				if($source['error'] !== null) {
					$failures[] = $location . ': ' . $source['error'];
					continue;
				}
				if(!$source['migration']) continue;
				$inspected++;
				if(!$source['migrationCertain']) {
					$warnings[] = $location . ': migration identity cannot be verified statically';
				}
				if($source['legacyMarker']) {
					$warnings[] = $location . ': legacy checksum validation is disabled for applied migrations';
				}
				if(!$source['downDeclared']) {
					$warnings[] = $location . ': down() is not yet declared';
				} elseif(!$source['downSignature']) {
					$warnings[] = $location . ': down() must be public function down(): void';
				}
			}
		}

		if($inspected === 0 && !$failures && !$warnings) {
			return new DiagnosticCheckResult(DiagnosticCheckResult::NOT_APPLICABLE, 'not_applicable', ['inspected' => 0]);
		}
		return $this->report($failures, $warnings, $inspected);
	}

}
