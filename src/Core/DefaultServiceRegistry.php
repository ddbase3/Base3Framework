<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IServiceRegistry;

/**
 * Class DefaultServiceRegistry
 *
 * Generic, lazy, cached registry for multiple named instances of a given service interface.
 *
 * - Construct with an interface FQCN (e.g. IFileStorage::class)
 * - Provide a default instance name (e.g. "default")
 * - Provide factories (closures) that create instances on demand
 *
 * Factories are called lazily on first access and the created instances are cached.
 * Each created instance is validated to implement the configured interface.
 */
class DefaultServiceRegistry implements IServiceRegistry {

	/** @var array<string, callable():object> */
	private array $factories;

	/** @var array<string, object> */
	private array $instances = [];

	public function __construct(
		private readonly string $interfaceFqcn,
		private readonly string $defaultName,
		array $factories
	) {
		$this->factories = $factories;
		$this->assertValidConfig();
	}

	public function get(string $name): object {
		if (!isset($this->factories[$name])) {
			throw new \RuntimeException("ServiceRegistry: unknown instance '{$name}' for {$this->interfaceFqcn}");
		}

		if (!isset($this->instances[$name])) {
			$factory = $this->factories[$name];

			$obj = $factory();
			if (!is_object($obj)) {
				$type = gettype($obj);
				throw new \RuntimeException("ServiceRegistry: factory for '{$name}' must return an object for {$this->interfaceFqcn}, got {$type}");
			}

			if (!is_a($obj, $this->interfaceFqcn)) {
				$type = get_class($obj);
				throw new \RuntimeException("ServiceRegistry: instance '{$name}' must implement {$this->interfaceFqcn}, got {$type}");
			}

			$this->instances[$name] = $obj;
		}

		return $this->instances[$name];
	}

	public function has(string $name): bool {
		return isset($this->factories[$name]);
	}

	public function getDefault(): object {
		return $this->get($this->defaultName);
	}

	public function listNames(): array {
		return array_keys($this->factories);
	}

	private function assertValidConfig(): void {
		if ($this->interfaceFqcn === '') {
			throw new \RuntimeException('ServiceRegistry: interfaceFqcn must not be empty');
		}

		if (!interface_exists($this->interfaceFqcn)) {
			throw new \RuntimeException("ServiceRegistry: interface '{$this->interfaceFqcn}' does not exist");
		}

		if ($this->defaultName === '') {
			throw new \RuntimeException('ServiceRegistry: defaultName must not be empty');
		}

		if (!isset($this->factories[$this->defaultName])) {
			throw new \RuntimeException("ServiceRegistry: default instance '{$this->defaultName}' is not defined for {$this->interfaceFqcn}");
		}

		foreach ($this->factories as $name => $factory) {
			if (!is_string($name) || $name === '') {
				throw new \RuntimeException("ServiceRegistry: instance names must be non-empty strings for {$this->interfaceFqcn}");
			}

			if (!is_callable($factory)) {
				throw new \RuntimeException("ServiceRegistry: factory for '{$name}' is not callable for {$this->interfaceFqcn}");
			}
		}
	}

}
