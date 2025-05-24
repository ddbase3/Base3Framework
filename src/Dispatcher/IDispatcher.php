<?php declare(strict_types=1);

namespace Base3\Dispatcher;

/**
 * Interface IDispatcher
 * Gemeinsame Schnittstelle für HookManager und EventManager
 */
interface IDispatcher
{
    /**
     * Löst das Event/Hooks aus und übergibt ein Event-Objekt oder Argumente.
     * Gibt ggf. das Event-Objekt zurück (bei EventManager) oder ein Ergebnis-Array (bei HookManager).
     *
     * @param object|string $event   Event-Objekt oder Event-Name (für HookManager)
     * @param mixed ...$args         Optionale Argumente (für HookManager)
     *
     * @return mixed
     */
    public function dispatch(object|string $event, ...$args);
}

