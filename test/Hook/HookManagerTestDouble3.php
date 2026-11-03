<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use Base3\Hook\Api\IHookListener;

final class HookManagerTestDouble3 implements IHookListener  {
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
			}
