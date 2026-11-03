<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use Base3\Hook\Api\IHookListener;

final class HookManagerTestDouble10 implements IHookListener {
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
			}
