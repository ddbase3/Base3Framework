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

namespace Base3\Core;

use Base3\Api\IOutput;

class PhpInfo implements IOutput {

	public function __construct() {
	}

	// Implementation of IBase

	public static function getName(): string {
		return "phpinfo";
	}

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {

                if (!getenv('DEBUG')) return '';

		return (string)phpinfo();
	}

	public function getHelp(): string {
		return 'Shows output of phpinfo();' . "\n";
	}
}
