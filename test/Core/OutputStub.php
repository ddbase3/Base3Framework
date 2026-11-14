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

namespace Base3\Test\Core;

use Base3\CoreFoundation\Api\IOutput;

/**
 * Class OutputStub
 *
 * Simple, DI-free output stub for unit tests.
 */
class OutputStub implements IOutput {

	private string $name;
	private $outputCallback;
	private $helpCallback;

	public function __construct(string $name = 'index', ?callable $outputCallback = null, ?callable $helpCallback = null) {
		$this->name = $name;
		$this->outputCallback = $outputCallback ?? function(string $out): string {
			return 'OUT:' . $out;
		};
		$this->helpCallback = $helpCallback ?? function(): string {
			return 'HELP';
		};
	}

	public static function getName(): string {
		// Default fallback; most tests should override via constructor name + ClassMapStub registration.
		return 'outputstub';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$cb = $this->outputCallback;
		return $cb((string)$out);
	}

	public function getHelp(): string {
		$cb = $this->helpCallback;
		return $cb();
	}

	public function withName(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getRegisteredName(): string {
		return $this->name;
	}
}
