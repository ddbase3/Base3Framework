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

namespace Base3\Test\Hook {

	use PHPUnit\Framework\TestCase;
	use Base3\Core\Hook\HookManager;
	use Base3\CoreFoundation\Hook\Api\IHookListener;

	class HookManagerTest extends TestCase
	{
		public function testDispatchReturnsEmptyArrayIfNoListenersRegistered(): void
		{
			$manager = new HookManager();
			$this->assertSame([], $manager->dispatch('bootstrap.init'));
		}

		public function testAddHookListenerRegistersListenerForSubscribedHookAndDispatchCallsHandle(): void
		{
			$manager = new HookManager();

			$listener = new HookManagerTestDouble1();

			$manager->addHookListener($listener);

			$result = $manager->dispatch('bootstrap.init', 1, 'x');

			$this->assertSame(['ok'], $result);
			$this->assertCount(1, $listener->calls);
			$this->assertSame('bootstrap.init', $listener->calls[0]['hook']);
			$this->assertSame([1, 'x'], $listener->calls[0]['args']);
		}

		public function testDispatchSkipsInactiveListeners(): void
		{
			$manager = new HookManager();

			$inactive = new HookManagerTestDouble2();

			$active = new HookManagerTestDouble3();

			$manager->addHookListener($inactive);
			$manager->addHookListener($active);

			$result = $manager->dispatch('bootstrap.init');

			$this->assertSame(['active'], $result);
			$this->assertSame(0, $inactive->handleCalls);
			$this->assertSame(1, $active->handleCalls);
		}

		public function testDispatchOrdersByPriorityDescending(): void
		{
			$manager = new HookManager();

			$low = new HookManagerTestDouble4();

			$high = new HookManagerTestDouble5();

			$manager->addHookListener($low);
			$manager->addHookListener($high);

			$result = $manager->dispatch('bootstrap.init');

			// krsort => 20 first, then 5
			$this->assertSame(['high', 'low'], $result);
		}

		public function testDispatchKeepsRegistrationOrderWithinSamePriority(): void
		{
			$manager = new HookManager();

			$a = new HookManagerTestDouble6();

			$b = new HookManagerTestDouble7();

			$manager->addHookListener($a);
			$manager->addHookListener($b);

			$result = $manager->dispatch('bootstrap.init');

			$this->assertSame(['a', 'b'], $result);
		}

		public function testListenerCanSubscribeToMultipleHooks(): void
		{
			$manager = new HookManager();

			$listener = new HookManagerTestDouble8();

			$manager->addHookListener($listener);

			$this->assertSame(['bootstrap.init'], $manager->dispatch('bootstrap.init'));
			$this->assertSame(['bootstrap.finish'], $manager->dispatch('bootstrap.finish'));
			$this->assertSame(2, $listener->calls);
		}

		public function testDispatchWithObjectUsesClassNameAsEventName(): void
		{
			$manager = new HookManager();

			$event = new HookManagerTestDouble9();

			$eventName = get_class($event);

			$listener = new HookManagerTestDouble10($eventName);

			$manager->addHookListener($listener);

			$result = $manager->dispatch($event);

			$this->assertSame([$eventName], $result);
		}
	}
}

namespace Base3\Test\Hook {

require_once __DIR__ . '/HookManagerTestDouble1.php';

require_once __DIR__ . '/HookManagerTestDouble2.php';

require_once __DIR__ . '/HookManagerTestDouble3.php';

require_once __DIR__ . '/HookManagerTestDouble4.php';

require_once __DIR__ . '/HookManagerTestDouble5.php';

require_once __DIR__ . '/HookManagerTestDouble6.php';

require_once __DIR__ . '/HookManagerTestDouble7.php';

require_once __DIR__ . '/HookManagerTestDouble8.php';

require_once __DIR__ . '/HookManagerTestDouble9.php';

require_once __DIR__ . '/HookManagerTestDouble10.php';
}
