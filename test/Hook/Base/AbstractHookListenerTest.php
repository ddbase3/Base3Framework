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

namespace Base3\Test\Hook\Base;

use Base3\Hook\Api\IHookListener;
use Base3\Hook\Base\AbstractHookListener;
use Base3\Hook\BootstrapLoggingHookListener;
use Base3\Hook\HookManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class AbstractHookListenerTest extends TestCase {

	public function testEmptySubclassDoesNotSubscribeOrRun(): void {
		$listener = new EmptyHookBaseFixture();
		$this->assertInstanceOf(IHookListener::class, $listener);
		$this->assertFalse($listener->isActive());
		$this->assertSame([], $listener::getSubscribedHooks());
		$this->assertNull($listener->handle('any.hook', 5));
		foreach ((new ReflectionClass(IHookListener::class))->getMethods() as $method) {
			$this->assertSame(AbstractHookListener::class, (new ReflectionMethod($listener, $method->getName()))->getDeclaringClass()->getName());
		}
	}

	public function testEmptyListenerDoesNotEnterHookDispatch(): void {
		$hooks = new HookManager();
		$hooks->addHookListener(new EmptyHookBaseFixture());
		$this->assertSame([], $hooks->dispatch('bootstrap.init'));
	}

	public function testConcreteListenerOverridesEveryMethod(): void {
		$this->assertTrue(is_subclass_of(BootstrapLoggingHookListener::class, AbstractHookListener::class));
		foreach ((new ReflectionClass(IHookListener::class))->getMethods() as $method) {
			$this->assertSame(BootstrapLoggingHookListener::class, (new ReflectionMethod(BootstrapLoggingHookListener::class, $method->getName()))->getDeclaringClass()->getName());
		}
		$this->assertSame([
			'bootstrap.init' => 10,
			'bootstrap.start' => 10,
			'bootstrap.finish' => 10,
		], BootstrapLoggingHookListener::getSubscribedHooks());
	}
}
