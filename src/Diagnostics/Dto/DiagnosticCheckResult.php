<?php declare(strict_types=1);

namespace Base3\Diagnostics\Dto;

use InvalidArgumentException;

/**
 * An immutable, translation-ready and privacy-minimized diagnostic outcome.
 *
 * The caller resolves messageKey/remediationKey against the check's module
 * translations. Replacements and details may only contain scalar values or
 * null; check implementations are responsible for excluding personal data.
 */
final readonly class DiagnosticCheckResult {

	public const PASSED = 'passed';
	public const WARNING = 'warning';
	public const FAILED = 'failed';
	public const ERROR = 'error';
	public const NOT_APPLICABLE = 'not_applicable';

	/**
	 * @param string $status One of the five public status constants
	 * @param string $messageKey Translation key in lang/DiagnosticChecks, e.g. 'failed'
	 * @param array<string, scalar|null> $replacements Values for {placeholder} substitutions
	 * @param string $remediationKey Optional translation key for a suggested manual action
	 * @param array<string, scalar|null> $details Optional named non-sensitive measurements
	 * @throws InvalidArgumentException On unknown status, blank message or non-scalar values
	 */
	public function __construct(
		public string $status,
		public string $messageKey,
		public array $replacements = [],
		public string $remediationKey = '',
		public array $details = []
	) {
		if(!in_array($status, [self::PASSED, self::WARNING, self::FAILED, self::ERROR, self::NOT_APPLICABLE], true)) {
			throw new InvalidArgumentException('Unknown diagnostic status.');
		}
		if(trim($messageKey) === '') {
			throw new InvalidArgumentException('A diagnostic message key is required.');
		}
		foreach([$replacements, $details] as $values) {
			foreach($values as $key => $value) {
				if(!is_string($key) || $key === '' || (!is_scalar($value) && $value !== null)) {
					throw new InvalidArgumentException('Diagnostic values require named scalar entries.');
				}
			}
		}
	}

}
