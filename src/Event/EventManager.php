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

use Base3\Event\Api\IEventManager;

class EventManager implements IEventManager {
	/** @var array<string, array<int, array<callable>>> */
	protected array $listeners = [];

	public function on(string $event, callable $listener, int $priority = 0): void {
		$this->listeners[$event][$priority][] = $listener;
	}

	public function once(string $event, callable $listener, int $priority = 0): void {
		$wrapper = null;
		$wrapper = function (...$args) use (&$wrapper, $event, $listener) {
			$this->off($event, $wrapper);
			return $listener(...$args);
		};

		$this->on($event, $wrapper, $priority);
	}

	public function off(string $event, callable $listener): void {
		if (!isset($this->listeners[$event])) {
			return;
		}

		foreach ($this->listeners[$event] as $priority => $handlers) {
			foreach ($handlers as $i => $handler) {
				if ($handler === $listener) {
					unset($this->listeners[$event][$priority][$i]);
				}
			}

			if (empty($this->listeners[$event][$priority])) {
				unset($this->listeners[$event][$priority]);
			}
		}

		if (empty($this->listeners[$event])) {
			unset($this->listeners[$event]);
		}
	}

	public function fire(object|string $event, ...$args): array {
		$eventName = \is_string($event) ? $event : \get_class($event);
		$listeners = $this->collectMatchingListeners($eventName);

		$results = [];
		foreach ($listeners as $listener) {
			$results[] = $listener($event, ...$args);

			if (\is_object($event) && $event instanceof StoppableEvent && $event->isPropagationStopped()) {
				break;
			}
		}

		return $results;
	}

	protected function collectMatchingListeners(string $eventName): array {
		$matchedListeners = [];

		foreach ($this->listeners as $pattern => $priorityMap) {
			if (!\fnmatch($pattern, $eventName)) {
				continue;
			}

			\krsort($priorityMap);

			foreach ($priorityMap as $listeners) {
				foreach ($listeners as $listener) {
					$matchedListeners[] = $listener;
				}
			}
		}

		return $matchedListeners;
	}
}
