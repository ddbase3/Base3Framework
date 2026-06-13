<?php declare(strict_types=1);

namespace Base3\Test\Core;

use Base3\Api\IClassMap;

class ClassMapStub implements IClassMap {

	/**
	 * Call log for assertions in unit tests.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public array $calls = [];

	private array $apps = [];
	private array $instancesByClass = [];

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

	/**
	 * Register a concrete instance and optionally bind it to:
	 * - a logical name (for getInstanceBy*Name lookups)
	 * - one or more interfaces
	 *
	 * This is crucial when tests expect identity (assertSame) and when
	 * anonymous classes are used.
	 */
	public function registerInstance(object $instance, ?string $name = null, array $interfaces = [], string $app = 'default'): self {
		if (!isset($this->apps[$app])) {
			$this->apps[$app] = ['interface' => [], 'name' => []];
		}

		$class = get_class($instance);
		$this->instancesByClass[$class] = $instance;

		if ($name !== null && strlen($name)) {
			$this->apps[$app]['name'][$name] = $class;
		}

		if (empty($interfaces)) {
			$interfaces = class_implements($class) ?: [];
		}

		foreach ($interfaces as $iface) {
			$this->apps[$app]['interface'][$iface][] = $class;
		}

		return $this;
	}

	// ---------------------------------------------------------------------
	// IClassMap
	// ---------------------------------------------------------------------

	public function instantiate(string $class) {
		$this->calls[] = [
			'method' => 'instantiate',
			'class' => $class
		];

		if (isset($this->instancesByClass[$class])) {
			return $this->instancesByClass[$class];
		}

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
		$this->calls[] = [
			'method' => 'getApps'
		];
		return array_keys($this->apps);
	}

	public function getPlugins() {
		$this->calls[] = [
			'method' => 'getPlugins'
		];
		return [];
	}

	public function &getInstances(array $criteria = []) {
		$this->calls[] = [
			'method' => 'getInstances',
			'criteria' => $criteria
		];

		$instances = [];

		$app = $criteria['app'] ?? null;
		$interface = $criteria['interface'] ?? null;
		$name = $criteria['name'] ?? null;

		if ($app && $interface && $name) {
			$inst = $this->getInstanceByAppInterfaceName($app, $interface, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		if ($app && $interface) {
			$instances = $this->getInstancesByAppInterface($app, $interface);
			return $instances;
		}

		if ($app && $name) {
			$inst = $this->getInstanceByAppName($app, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		if ($interface && $name) {
			$inst = $this->getInstanceByInterfaceName($interface, $name);
			if ($inst) $instances[] = $inst;
			return $instances;
		}

		if ($interface) {
			$instances = $this->getInstancesByInterface($interface);
			return $instances;
		}

		if ($name) {
			foreach ($this->apps as $appName => $data) {
				if (!isset($data['name'][$name])) continue;
				$c = $data['name'][$name];
				$inst = $this->instantiate($c);
				if ($inst) $instances[] = $inst;
			}
			return $instances;
		}

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
		$this->calls[] = [
			'method' => 'getInstancesByInterface',
			'interface' => $interface
		];

		$instances = [];
		foreach ($this->apps as $app => $data) {
			$is = $this->getInstancesByAppInterface($app, $interface);
			$instances = array_merge($instances, $is);
		}
		return $instances;
	}

	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		$this->calls[] = [
			'method' => 'getInstancesByAppInterface',
			'app' => $app,
			'interface' => $interface,
			'retry' => $retry
		];

		$instances = [];

		if (!isset($this->apps[$app]['interface'][$interface])) return $instances;

		foreach ($this->apps[$app]['interface'][$interface] as $c) {
			$inst = $this->instantiate($c);
			if ($inst) $instances[] = $inst;
		}

		return $instances;
	}

	public function &getInstanceByAppName($app, $name, $retry = false) {
		$this->calls[] = [
			'method' => 'getInstanceByAppName',
			'app' => $app,
			'name' => $name,
			'retry' => $retry
		];

		$instance = null;

		if (!isset($this->apps[$app]['name'][$name])) return $instance;

		$c = $this->apps[$app]['name'][$name];
		$instance = $this->instantiate($c);

		return $instance;
	}

	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		$this->calls[] = [
			'method' => 'getInstanceByInterfaceName',
			'interface' => $interface,
			'name' => $name,
			'retry' => $retry
		];

		$instance = null;

		foreach ($this->apps as $app => $data) {
			if (!isset($data['name'][$name])) continue;
			if (!isset($data['interface'][$interface])) continue;

			$c = $data['name'][$name];

			// Registry-first: ensure class is part of the interface mapping for this app
			if (!in_array($c, $data['interface'][$interface], true)) continue;

			$instance = $this->instantiate($c);
			return $instance;
		}

		return $instance;
	}

	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		$this->calls[] = [
			'method' => 'getInstanceByAppInterfaceName',
			'app' => $app,
			'interface' => $interface,
			'name' => $name,
			'retry' => $retry
		];

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
