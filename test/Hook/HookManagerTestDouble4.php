<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use Base3\Hook\Api\IHookListener;

final class HookManagerTestDouble4 implements IHookListener  {
				public function isActive(): bool { return true; }
				public static function getSubscribedHooks(): array { return ['bootstrap.init' => 5]; }
				public function handle(string $hookName, ...$args) { return 'low'; }
			}
