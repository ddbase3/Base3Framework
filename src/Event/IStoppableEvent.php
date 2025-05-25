<?php declare(strict_types=1);

namespace Base3\Event;

interface IStoppableEvent {
	public function stopPropagation(): void;
	public function isPropagationStopped(): bool;
}

