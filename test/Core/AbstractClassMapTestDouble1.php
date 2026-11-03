<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IBase;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Core\AbstractClassMap;
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
