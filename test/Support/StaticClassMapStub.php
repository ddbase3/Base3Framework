<?php declare(strict_types=1);

namespace Base3\Test\Support;

use Base3\Api\IClassMap;

/** Deterministic test registry for classes provided to a single unit under test. */
final class StaticClassMapStub implements IClassMap {

	/** @var array<string, object> */
	private array $byName = [];
	/** @var array<int, object> */
	private array $instances = [];
	private ?object $selected = null;
	public ?object $instantiateResult = null;
	public array $instantiateWithCalls = [];

	public function register(string $name, object $instance): void {
		$this->byName[$name] = $instance;
		$this->instances[] = $instance;
	}

	public function instantiate(string $class) { return null; }
	public function instantiateWith(string $class, array $arguments = []) {
		$this->instantiateWithCalls[] = [$class, $arguments];
		return $this->instantiateResult;
	}
	public function generate($regenerate = false): void {}
	public function getApps() { return []; }
	public function &getInstances(array $criteria = []) { return $this->instances; }
	public function &getInstancesByInterface($interface) {
		$results = array_values(array_filter($this->instances, static fn(object $instance): bool => $instance instanceof $interface));
		return $results;
	}
	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		return $this->getInstancesByInterface($interface);
	}
	public function &getInstanceByAppName($app, $name, $retry = false) { return $this->getInstanceByInterfaceName('', $name); }
	public function getClassByInterfaceName(string $interface, string $name): ?string {
		return isset($this->byName[$name]) ? $this->byName[$name]::class : null;
	}
	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		$this->selected = $this->byName[$name] ?? null;
		return $this->selected;
	}
	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		return $this->getInstanceByInterfaceName($interface, $name, $retry);
	}
	public function getPlugins() { return []; }
}
