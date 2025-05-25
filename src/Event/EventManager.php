<?php declare(strict_types=1);

namespace Base3\Event;

class EventManager implements IEventManager
{
    /** @var array<string, array<int, array<callable>>> */
    protected array $listeners = [];

    public function on(string $event, callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
    }

    public function once(string $event, callable $listener, int $priority = 0): void
    {
        $wrapper = null;
        $wrapper = function (...$args) use (&$wrapper, $event, $listener) {
            $this->off($event, $wrapper);
            return $listener(...$args);
        };
        $this->on($event, $wrapper, $priority);
    }

    public function off(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) return;

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

    public function fire(object|string $event, ...$args): array
    {
        $eventName = \is_string($event) ? $event : \get_class($event);
        $allListeners = $this->collectMatchingListeners($eventName);

        $results = [];
        foreach ($allListeners as $listener) {
            $result = $listener($event, ...$args);
            $results[] = $result;

            if (\is_object($event) && $event instanceof StoppableEvent && $event->isPropagationStopped()) {
                break;
            }
        }

        return $results;
    }

    protected function collectMatchingListeners(string $eventName): array
    {
        $matched = [];

        foreach ($this->listeners as $pattern => $priorityMap) {
            if (\fnmatch($pattern, $eventName)) {
                \krsort($priorityMap);
                foreach ($priorityMap as $listeners) {
                    foreach ($listeners as $listener) {
                        $matched[] = $listener;
                    }
                }
            }
        }

        return $matched;
    }
}

