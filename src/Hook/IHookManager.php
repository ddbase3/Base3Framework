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

