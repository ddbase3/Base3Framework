<?php declare(strict_types=1);

namespace Base3\Event;

class BaseEvent implements IStoppableEvent {

	protected bool $stopped = false;

	public function stopPropagation(): void {
		$this->stopped = true;
	}

	public function isPropagationStopped(): bool {
		return $this->stopped;
	}
}

