<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Diagnostics;

use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
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
