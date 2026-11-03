<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use Base3\Hook\Api\IHookListener;

final class HookManagerTestDouble8 implements IHookListener  {
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
			}
