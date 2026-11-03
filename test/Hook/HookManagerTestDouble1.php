<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use Base3\Hook\Api\IHookListener;

final class HookManagerTestDouble1 implements IHookListener  {
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
			}
