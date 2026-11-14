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

namespace Base3\Test\Diagnostics\Check;

use Base3\CoreFoundation\Api\IModuleRegistry;
use RuntimeException;

/** Isolated registry exposing only the fixture's explicitly installed modules. */
final class DiagnosisTestModuleRegistry implements IModuleRegistry {

	/** @param array<string,string> $paths */
	public function __construct(private readonly array $paths = []) {}

	public function getModuleNames(): array {
		return array_keys($this->paths);
	}

	public function getModulePath(string $name): ?string {
		return $this->paths[$name] ?? null;
	}

	public function requireModulePath(string $name): string {
		return $this->paths[$name] ?? throw new RuntimeException('Unknown module: ' . $name);
	}

}
