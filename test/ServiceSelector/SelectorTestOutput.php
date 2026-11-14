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

namespace Base3\Test\ServiceSelector;

use Base3\CoreFoundation\Api\IOutput;
use Base3\CoreFoundation\Api\IHelp;

final class SelectorTestOutput implements IOutput, IHelp {
	public array $calls = [];
	public static function getName(): string { return 'selector-test-output'; }
	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->calls[] = [$out, $final];
		return 'response:' . $out;
	}
	public function getHelp(): string { return 'selector help'; }
}
