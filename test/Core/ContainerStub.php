<?php declare(strict_types=1);

namespace Base3\Test\Core;

use Base3\Api\IContainer;

/**
 * Class ContainerStub
 *
 * Simple, DI-free container stub for unit tests.
 *
 * Features:
 * - set/has/get/remove
 * - supports flags: SHARED, NOOVERWRITE, ALIAS, PARAMETER
 * - resolves callables as factories: fn(IContainer $c) => object
 * - caches SHARED instances
 */
class ContainerStub implements IContainer {

	private array $items = [];
	private array $flags = [];
	private array $instances = [];

	public function getServiceList(): array {
		return array_keys($this->items);
	}

	public function set(string $name, $classDefinition, $flags = 0): IContainer {
		$flags = (int)$flags;

		if (($flags & self::NOOVERWRITE) === self::NOOVERWRITE && $this->has($name)) {
			return $this;
		}

		$this->items[$name] = $classDefinition;
		$this->flags[$name] = $flags;

		// Reset instance cache on overwrite to keep behavior predictable in tests.
		unset($this->instances[$name]);

		return $this;
	}

	public function remove(string $name) {
		unset($this->items[$name], $this->flags[$name], $this->instances[$name]);
	}

	public function has(string $name): bool {
		return array_key_exists($name, $this->items);
	}

	public function get(string $name) {
		if (!$this->has($name)) return null;

		$flags = (int)($this->flags[$name] ?? 0);
		$definition = $this->items[$name];

		// Alias handling: value is the target service name
		if (($flags & self::ALIAS) === self::ALIAS) {
			if (!is_string($definition) || $definition === '') return null;
			if ($definition === $name) return null; // prevent self-alias loops
			return $this->get($definition);
		}

		// SHARED cache
		if (($flags & self::SHARED) === self::SHARED && array_key_exists($name, $this->instances)) {
			return $this->instances[$name];
		}

		// PARAMETER: return as-is, but still allow SHARED caching (harmless)
		if (($flags & self::PARAMETER) === self::PARAMETER) {
			if (($flags & self::SHARED) === self::SHARED) {
				$this->instances[$name] = $definition;
			}
			return $definition;
		}

		// Factories (closures / callables) are resolved with the container as argument
		if (is_callable($definition)) {
			$resolved = $definition($this);

			if (($flags & self::SHARED) === self::SHARED) {
				$this->instances[$name] = $resolved;
			}

			return $resolved;
		}

		// Direct object/value/classname string (DI-free, no auto-instantiation)
		if (($flags & self::SHARED) === self::SHARED) {
			$this->instances[$name] = $definition;
		}

		return $definition;
	}

	// Optional helper for tests (not part of IContainer, but handy)
	public function getFlags(string $name): ?int {
		return $this->flags[$name] ?? null;
	}
}
