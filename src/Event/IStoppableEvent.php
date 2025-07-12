<?php declare(strict_types=1);

namespace Base3\Event;

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

