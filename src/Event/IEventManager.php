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

namespace Base3\Event;

/**
 * Interface IEventManager
 *
 * Defines a generic event manager for registering and triggering event listeners.
 */
interface IEventManager {

	/**
	 * Registers a listener for a specific event.
	 *
	 * @param string $event Event name
	 * @param callable $listener Listener callback
	 * @param int $priority Execution priority (higher = earlier), default: 0
	 * @return void
	 */
	public function on(string $event, callable $listener, int $priority = 0): void;

	/**
	 * Registers a one-time listener for a specific event.
	 *
	 * The listener will be automatically removed after the first execution.
	 *
	 * @param string $event Event name
	 * @param callable $listener Listener callback
	 * @param int $priority Execution priority (higher = earlier), default: 0
	 * @return void
	 */
	public function once(string $event, callable $listener, int $priority = 0): void;

	/**
	 * Removes a previously registered listener for a specific event.
	 *
	 * @param string $event Event name
	 * @param callable $listener Listener callback to remove
	 * @return void
	 */
	public function off(string $event, callable $listener): void;

	/**
	 * Fires an event and calls all registered listeners.
	 *
	 * @param object|string $event An event object or event name
	 * @param mixed ...$args Additional arguments passed to the listeners
	 * @return array<int, mixed> Return values from all listeners
	 */
	public function fire(object|string $event, ...$args): array;

}

