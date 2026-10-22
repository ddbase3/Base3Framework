<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics;

use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DiagnosticCheckResultTest extends TestCase {

	public function testCreatesTranslationReadyResult(): void {
		$result = new DiagnosticCheckResult(
			DiagnosticCheckResult::FAILED,
			'failed',
			['count' => 3],
			'remediation',
			['overdue' => 3]
		);
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertSame(['overdue' => 3], $result->details);
		$this->assertSame('remediation', $result->remediationKey);
	}

	public function testRejectsAnUnknownStatus(): void {
		$this->expectException(InvalidArgumentException::class);
		new DiagnosticCheckResult('ok', 'passed');
	}

	public function testRejectsStructuredOrSensitiveReplacementPayloads(): void {
		$this->expectException(InvalidArgumentException::class);
		new DiagnosticCheckResult(DiagnosticCheckResult::PASSED, 'passed', ['unsafe' => ['not', 'scalar']]);
	}

}
