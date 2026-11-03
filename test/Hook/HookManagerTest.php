<?php declare(strict_types=1);

/**
 * IMPORTANT:
 * This test file MUST NOT change anything outside itself.
 * We suppress BootstrapLoggingHookListener output by overriding error_log()
 * in the Base3\Hook namespace (where the listener lives).
 */

namespace Base3\Hook {
	/**
	 * Silence error_log calls originating from Base3\Hook classes during tests.
	 * BootstrapLoggingHookListener calls error_log() unqualified, so PHP resolves to this.
	 */
	function error_log(string $message, int $message_type = 0, ?string $destination = null, ?string $extra_headers = null): bool
	{
		return true;
	}
}

namespace Base3\Test\Hook {

	use PHPUnit\Framework\TestCase;
	use Base3\Hook\HookManager;
	use Base3\Hook\Api\IHookListener;

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
