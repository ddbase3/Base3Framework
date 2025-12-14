<?php declare(strict_types=1);

namespace Base3\Hook;

class HookManager implements IHookManager {
	/** @var array<string, array<int, IHookListener[]>> */
	protected array $listeners = [];

	/**
	 * Adds a listener instance (with getSubscribedHooks).
	 *
	 * @param IHookListener $listener
	 */
	public function addHookListener(IHookListener $listener): void {
		foreach ($listener::getSubscribedHooks() as $hookName => $priority) {
			if (!isset($this->listeners[$hookName])) {
				$this->listeners[$hookName] = [];
			}
			if (!isset($this->listeners[$hookName][$priority])) {
				$this->listeners[$hookName][$priority] = [];
			}
			$this->listeners[$hookName][$priority][] = $listener;
		}
	}

	/**
	 * Dispatches the hook.
	 *
	 * @param object|string $event
	 * @param mixed         ...$args
	 * @return array
	 */
	public function dispatch(object|string $event, ...$args): array {
		$eventName = is_string($event) ? $event : get_class($event);

		if (!isset($this->listeners[$eventName])) {
			return [];
		}

		$results = [];
		krsort($this->listeners[$eventName]);

		foreach ($this->listeners[$eventName] as $priorityListeners) {
			foreach ($priorityListeners as $listener) {
				if ($listener->isActive()) {
					// Pass the event name as the first argument
					$results[] = $listener->handle($eventName, ...$args);
				}
			}
		}

		return $results;
	}
}
