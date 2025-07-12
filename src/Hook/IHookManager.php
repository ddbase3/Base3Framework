<?php declare(strict_types=1);

namespace Base3\Hook;

/**
 * Interface IHookManager
 *
 * Manages and dispatches hook listeners in the system.
 */
interface IHookManager {

	/**
	 * Dispatches a hook event to all registered listeners.
	 *
	 * @param object|string $event Either a hook object or a hook name
	 * @param mixed ...$args Optional arguments passed to listeners
	 * @return array<int, mixed> Return values from listeners
	 */
	public function dispatch(object|string $event, ...$args);

	/**
	 * Registers a hook listener.
	 *
	 * @param IHookListener $listener The listener instance to add
	 * @return void
	 */
	public function addHookListener(IHookListener $listener): void;

}

