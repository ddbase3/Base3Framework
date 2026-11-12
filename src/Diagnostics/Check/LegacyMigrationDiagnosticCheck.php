<?php declare(strict_types=1);

namespace Base3\Diagnostics\Check;

use Base3\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Inspection\ModuleSourceInventory;
use Base3\Diagnostics\Inspection\PhpSourceInspector;
use Base3\Diagnostics\Scope\InstallationDiagnosticScope;

/** Counts staged legacy checksum exemptions separately from down() readiness. */
final class LegacyMigrationDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'legacymigrationdiagnosticcheck';
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
		$legacy = [];

		foreach($roots['roots'] as $root) {
			$manifest = $inventory->manifest($root);
			if($manifest['errors']) {
				$failures[] = basename($root) . ': cannot inspect legacy markers without a valid base3.json';
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
				if($source['legacyMarker']) $legacy[] = $location;
			}
		}

		if($inspected === 0 && !$failures && !$warnings) {
			return new DiagnosticCheckResult(DiagnosticCheckResult::NOT_APPLICABLE, 'not_applicable', ['inspected' => 0]);
		}
		$legacyCount = count($legacy);
		$sample = implode('; ', array_slice($legacy, 0, 4));
		if($legacyCount > 4) $sample .= '; ... (' . ($legacyCount - 4) . ' more)';
		if($failures) {
			$status = DiagnosticCheckResult::FAILED;
		} elseif($warnings || $legacyCount > 0) {
			$status = DiagnosticCheckResult::WARNING;
		} else {
			$status = DiagnosticCheckResult::PASSED;
		}
		if($failures) $sample = implode('; ', array_slice($failures, 0, 3)) . ($sample === '' ? '' : '; ' . $sample);
		if($warnings) $sample .= ($sample === '' ? '' : '; ') . implode('; ', array_slice($warnings, 0, 2));
		$sample = (string)preg_replace('/[<>&"\x00-\x1F\x7F]/u', '?', $sample);
		return new DiagnosticCheckResult(
			$status,
			$status,
			['inspected' => $inspected, 'legacy' => $legacyCount, 'failed' => count($failures), 'warning' => count($warnings), 'unverifiable' => count($warnings), 'sample' => substr($sample, 0, 850)],
			$status === DiagnosticCheckResult::PASSED ? '' : 'remediation',
			['inspected' => $inspected, 'legacy' => $legacyCount, 'failed' => count($failures), 'unverifiable' => count($warnings)]
		);
	}

}
