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

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticScope;
use Base3\Core\Diagnostics\DiagnosticRunner;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Core\Diagnostics\Scope\PrivacyDiagnosticScope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DiagnosticRunnerTestCheck implements IDiagnosticCheck {

	public int $executions = 0;

	public static function getName(): string { return 'diagnosticrunnertestcheck'; }
	public function getScope(): string { return PrivacyDiagnosticScope::getName(); }
	public function run(): DiagnosticCheckResult {
		$this->executions++;
		throw new RuntimeException('Potential sensitive exception detail');
	}

}
