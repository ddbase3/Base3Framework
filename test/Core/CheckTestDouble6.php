<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IContainer;
use Base3\Api\ICheck;
use Base3\Core\Check;
use PHPUnit\Framework\TestCase;

final class CheckTestDouble6 implements IContainer {

			private array $services;
			private array $throwOnGet;

			public function __construct(array $services, array $throwOnGet) {
				$this->services = $services;
				$this->throwOnGet = $throwOnGet;
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
				if (isset($this->throwOnGet[$name])) {
					throw $this->throwOnGet[$name];
				}
				return $this->services[$name] ?? null;
			}
		}
