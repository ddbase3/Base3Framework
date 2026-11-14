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

use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\ComposerClassMap;
use PHPUnit\Framework\TestCase;

final class ComposerClassMapTestDouble1 implements IContainer  {
			public function getServiceList(): array { return []; }
			public function set(string $name, $classDefinition, $flags = 0): IContainer { return $this; }
			public function remove(string $name) {}
			public function has(string $name): bool { return false; }
			public function get(string $name) { return null; }
		}
