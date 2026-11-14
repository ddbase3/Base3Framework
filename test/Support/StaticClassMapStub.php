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

namespace Base3\Test\Support;

use Base3\CoreFoundation\Api\IClassMap;

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
