<?php declare(strict_types=1);

namespace Base3\Hook;

interface IHookManager {
	public function dispatch(object|string $event, ...$args);
	public function addHookListener(IHookListener $listener): void;
}

