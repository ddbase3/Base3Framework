<?php declare(strict_types=1);

namespace Base3\Test\Core;

use Base3\Api\IClassMap;

/**
 * Class ClassMapStub
 *
 * Simple, DI-free in-memory stub for IClassMap.
 * Useful for unit tests across plugins.
 */
class ClassMapStub implements IClassMap {

	private array $apps = [];

	public function __construct() {
		$this->apps['default'] = [
			'interface' => [],
			'name' => []
		];
	}

	// ---------------------------------------------------------------------
	// Convenience registration for tests
	// ---------------------------------------------------------------------

	public function register(string $class, string $app = 'default'): self {
		if (!isset($this->apps[$app])) {
			$this->apps[$app] = ['interface' => [], 'name' => []];
		}

		if (!class_exists($class)) return $this;

		$interfaces = class_implements($class) ?: [];
		foreach ($interfaces as $iface) {
			$this->apps[$app]['interface'][$iface][] = $class;
		}

		if (is_callable([$class, 'getName'])) {
			try {
				$name = $class::getName();
				if (is_string($name) && strlen($name)) {
					$this->apps[$app]['name'][$name] = $class;
				}
			} catch (\Throwable $e) {
				// ignore
			}
		}

		return $this;
	}

	public function registerName(string $name, string $class, string $app = 'default'): self {
		if (!isset($this->apps[$app])) {
			$this->apps[$app] = ['interface' => [], 'name' => []];
		}
		$this->apps[$app]['name'][$name] = $class;
		return $this;
	}

	public function registerInterface(string $interface, string $class, string $app = 'default'): self {
		if (!isset($this->apps[$app])) {
			$this->apps[$app] = ['interface' => [], 'name' => []];
		}
		$this->apps[$app]['interface'][$interface][] = $class;
		return $this;
	}

	// ---------------------------------------------------------------------
	// IClassMap
	// ---------------------------------------------------------------------

	public function instantiate(string $class) {
		if (!class_exists($class)) return null;

		try {
			$refClass = new \ReflectionClass($class);
			if ($refClass->isAbstract()) return null;

			$constructor = $refClass->getConstructor();
			if (!$constructor || $constructor->getNumberOfRequiredParameters() === 0) {
				return new $class();
			}

			// DI-free: only instantiate if all required params have defaults
			$params = [];
			foreach ($constructor->getParameters() as $param) {
				if ($param->isDefaultValueAvailable()) {
					$params[] = $param->getDefaultValue();
					continue;
				}

				$type = $param->getType();
				if ($type instanceof \ReflectionNamedType && $type->allowsNull()) {
					$params[] = null;
					continue;
				}

				return null;
			}

			return $refClass->newInstanceArgs($params);
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function generate($regenerate = false): void {
		// no-op (in-memory stub)
	}

	public function getApps() {
		return array_keys($this->apps);
	}

	public function getPlugins() {
		return [];
	}

	public function &getInstances(array $criteria = []) {
		$instances = [];

		$app = $criteria['app'] ?? null;
		$interface = $criteria['interface'] ?? null;
		$name = $criteria['name'] ?? null;

		// app + interface + name
		if ($app && $interface && $name) {
			$inst = $this->getInstanceByAppInterfaceName($app, $interface, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		// app + interface
		if ($app && $interface) {
			$instances = $this->getInstancesByAppInterface($app, $interface);
			return $instances;
		}

		// app + name
		if ($app && $name) {
			$inst = $this->getInstanceByAppName($app, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		// interface + name
		if ($interface && $name) {
			$inst = $this->getInstanceByInterfaceName($interface, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		// interface
		if ($interface) {
			$instances = $this->getInstancesByInterface($interface);
			return $instances;
		}

		// name
		if ($name) {
			foreach ($this->apps as $appName => $data) {
				if (!isset($data['name'][$name])) continue;
				$c = $data['name'][$name];
				$inst = $this->instantiate($c);
				if ($inst) $instances[] = $inst;
			}
			return $instances;
		}

		// no criteria: all instances by name registry
		foreach ($this->apps as $data) {
			if (!isset($data['name'])) continue;
			foreach ($data['name'] as $c) {
				$inst = $this->instantiate($c);
				if ($inst) $instances[] = $inst;
			}
		}

		return $instances;
	}

	public function &getInstancesByInterface($interface) {
		$instances = [];
		foreach ($this->apps as $app => $data) {
			$is = $this->getInstancesByAppInterface($app, $interface);
			$instances = array_merge($instances, $is);
		}
		return $instances;
	}

	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		$instances = [];

		if (!isset($this->apps[$app]['interface'][$interface])) return $instances;

		foreach ($this->apps[$app]['interface'][$interface] as $c) {
			$inst = $this->instantiate($c);
			if ($inst) $instances[] = $inst;
		}

		return $instances;
	}

	public function &getInstanceByAppName($app, $name, $retry = false) {
		$instance = null;

		if (!isset($this->apps[$app]['name'][$name])) return $instance;

		$c = $this->apps[$app]['name'][$name];
		$instance = $this->instantiate($c);

		return $instance;
	}

	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		$instance = null;

		foreach ($this->apps as $data) {
			if (!isset($data['name'][$name])) continue;

			$c = $data['name'][$name];
			if (!class_exists($c)) continue;

			$ifaces = class_implements($c) ?: [];
			if (!in_array($interface, $ifaces)) continue;

			$instance = $this->instantiate($c);
			return $instance;
		}

		return $instance;
	}

	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		if (!strlen((string)$app)) return $this->getInstanceByInterfaceName($interface, $name);

		$instance = null;

		if (!isset($this->apps[$app]['name'][$name])) return $instance;
		if (!isset($this->apps[$app]['interface'][$interface])) return $instance;

		$c = $this->apps[$app]['name'][$name];
		if (!in_array($c, $this->apps[$app]['interface'][$interface])) return $instance;

		$instance = $this->instantiate($c);
		return $instance;
	}
}
