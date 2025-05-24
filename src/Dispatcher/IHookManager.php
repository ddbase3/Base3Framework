<?php declare(strict_types=1);

namespace Base3\Dispatcher;

interface IHookManager extends IDispatcher
{
	public function addHookListener(IHookListener $listener): void;
}

