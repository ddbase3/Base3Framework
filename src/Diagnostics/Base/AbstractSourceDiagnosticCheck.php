<?php declare(strict_types=1);

namespace Base3\Diagnostics\Base;

use Base3\Api\IModuleRegistry;
use Base3\Diagnostics\Api\IDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Inspection\ModuleSourceInventory;

/** Shared read-only inspection and safe result formatting for source diagnostics. */
abstract class AbstractSourceDiagnosticCheck implements IDiagnosticCheck {

	public function __construct(
		private readonly IModuleRegistry $modules,
		private readonly ?string $frameworkRoot = null,
		private readonly ?string $pluginsRoot = null
	) {}

	protected function inventory(): ModuleSourceInventory {
		return new ModuleSourceInventory($this->modules, $this->frameworkRoot, $this->pluginsRoot);
	}

	/**
	 * @param list<string> $failures
	 * @param list<string> $warnings
	 */
	protected function report(array $failures, array $warnings, int $inspected): DiagnosticCheckResult {
		$status = $failures ? DiagnosticCheckResult::FAILED : ($warnings ? DiagnosticCheckResult::WARNING : DiagnosticCheckResult::PASSED);
		$notes = array_merge($failures, $warnings);
		$sample = implode('; ', array_slice($notes, 0, 6));
		if(count($notes) > 6) {
			$sample .= '; ... (' . (count($notes) - 6) . ' more)';
		}
		$sample = (string)preg_replace('/[<>&"\x00-\x1F\x7F]/u', '?', $sample);
		return new DiagnosticCheckResult(
			$status,
			$status,
			['inspected' => $inspected, 'failed' => count($failures), 'warning' => count($warnings), 'sample' => substr($sample, 0, 850)],
			$status === DiagnosticCheckResult::PASSED ? '' : 'remediation',
			['inspected' => $inspected, 'failed' => count($failures), 'warning' => count($warnings)]
		);
	}

}
