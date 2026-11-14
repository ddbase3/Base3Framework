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

use Base3\CoreFoundation\Api\IBase;
use Base3\CoreFoundation\Api\ICheck;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Base\AbstractClassMap;
use PHPUnit\Framework\TestCase;

final class AbstractClassMapTestDouble1 implements IContainer {

			private array $services;

			public function __construct(array $services) {
				$this->services = $services;
			}

			public function getServiceList(): array {
				return array_keys($this->services);
			}

			public function set(string $name, $classDefinition, $flags = 0): IContainer {
				$this->services[$name] = $classDefinition;
				return $this;
			}

			public function remove(string $name) {
				unset($this->services[$name]);
			}

			public function has(string $name): bool {
				return array_key_exists($name, $this->services);
			}

			public function get(string $name) {
				return $this->services[$name] ?? null;
			}
		}
