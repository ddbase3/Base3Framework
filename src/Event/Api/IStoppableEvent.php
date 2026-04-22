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

namespace Base3\Event\Api;

/**
 * Interface IStoppableEvent
 *
 * Marks an event as stoppable, allowing listeners to interrupt event propagation.
 */
interface IStoppableEvent {

	/**
	 * Stops further propagation of the event.
	 *
	 * Once called, no additional listeners should be invoked.
	 *
	 * @return void
	 */
	public function stopPropagation(): void;

	/**
	 * Checks whether event propagation has been stopped.
	 *
	 * @return bool True if propagation is stopped, false otherwise
	 */
	public function isPropagationStopped(): bool;
}
