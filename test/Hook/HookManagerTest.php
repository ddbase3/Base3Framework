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
	use Base3\Hook\IHookListener;

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

			$listener = new class implements IHookListener {
				public bool $active = true;
				public array $calls = [];

				public function isActive(): bool
				{
					return $this->active;
				}

				public static function getSubscribedHooks(): array
				{
					return ['bootstrap.init' => 10];
				}

				public function handle(string $hookName, ...$args)
				{
					$this->calls[] = ['hook' => $hookName, 'args' => $args];
					return 'ok';
				}
			};

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

			$inactive = new class implements IHookListener {
				public int $handleCalls = 0;

				public function isActive(): bool
				{
					return false;
				}

				public static function getSubscribedHooks(): array
				{
					return ['bootstrap.init' => 10];
				}

				public function handle(string $hookName, ...$args)
				{
					$this->handleCalls++;
					return 'should-not-happen';
				}
			};

			$active = new class implements IHookListener {
				public int $handleCalls = 0;

				public function isActive(): bool
				{
					return true;
				}

				public static function getSubscribedHooks(): array
				{
					return ['bootstrap.init' => 10];
				}

				public function handle(string $hookName, ...$args)
				{
					$this->handleCalls++;
					return 'active';
				}
			};

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

			$low = new class implements IHookListener {
				public function isActive(): bool { return true; }
				public static function getSubscribedHooks(): array { return ['bootstrap.init' => 5]; }
				public function handle(string $hookName, ...$args) { return 'low'; }
			};

			$high = new class implements IHookListener {
				public function isActive(): bool { return true; }
				public static function getSubscribedHooks(): array { return ['bootstrap.init' => 20]; }
				public function handle(string $hookName, ...$args) { return 'high'; }
			};

			$manager->addHookListener($low);
			$manager->addHookListener($high);

			$result = $manager->dispatch('bootstrap.init');

			// krsort => 20 first, then 5
			$this->assertSame(['high', 'low'], $result);
		}

		public function testDispatchKeepsRegistrationOrderWithinSamePriority(): void
		{
			$manager = new HookManager();

			$a = new class implements IHookListener {
				public function isActive(): bool { return true; }
				public static function getSubscribedHooks(): array { return ['bootstrap.init' => 10]; }
				public function handle(string $hookName, ...$args) { return 'a'; }
			};

			$b = new class implements IHookListener {
				public function isActive(): bool { return true; }
				public static function getSubscribedHooks(): array { return ['bootstrap.init' => 10]; }
				public function handle(string $hookName, ...$args) { return 'b'; }
			};

			$manager->addHookListener($a);
			$manager->addHookListener($b);

			$result = $manager->dispatch('bootstrap.init');

			$this->assertSame(['a', 'b'], $result);
		}

		public function testListenerCanSubscribeToMultipleHooks(): void
		{
			$manager = new HookManager();

			$listener = new class implements IHookListener {
				public int $calls = 0;

				public function isActive(): bool
				{
					return true;
				}

				public static function getSubscribedHooks(): array
				{
					return [
						'bootstrap.init' => 10,
						'bootstrap.finish' => 10,
					];
				}

				public function handle(string $hookName, ...$args)
				{
					$this->calls++;
					return $hookName;
				}
			};

			$manager->addHookListener($listener);

			$this->assertSame(['bootstrap.init'], $manager->dispatch('bootstrap.init'));
			$this->assertSame(['bootstrap.finish'], $manager->dispatch('bootstrap.finish'));
			$this->assertSame(2, $listener->calls);
		}

		public function testDispatchWithObjectUsesClassNameAsEventName(): void
		{
			$manager = new HookManager();

			$event = new class {
			};

			$eventName = get_class($event);

			$listener = new class($eventName) implements IHookListener {
				public static string $hook = '';

				public function __construct(string $eventName)
				{
					self::$hook = $eventName;
				}

				public function isActive(): bool
				{
					return true;
				}

				public static function getSubscribedHooks(): array
				{
					return [self::$hook => 10];
				}

				public function handle(string $hookName, ...$args)
				{
					return $hookName;
				}
			};

			$manager->addHookListener($listener);

			$result = $manager->dispatch($event);

			$this->assertSame([$eventName], $result);
		}
	}
}
