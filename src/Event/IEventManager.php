<?php declare(strict_types=1);

namespace Base3\Event;

interface IEventManager {
	public function on(string $event, callable $listener, int $priority = 0): void;
	public function once(string $event, callable $listener, int $priority = 0): void;
	public function off(string $event, callable $listener): void;

	/**
	 * @param object|string $event Ein Event-Objekt oder Event-Name
	 * @param mixed         ...$args Weitere Argumente
	 * @return array<int, mixed> Rückgaben der Listener
	 */
	public function fire(object|string $event, ...$args): array;
}

