<?php declare(strict_types=1);

namespace Base3\Test\Hook;

use Base3\Hook\Api\IHookListener;

final class HookManagerTestDouble5 implements IHookListener  {
				public function isActive(): bool { return true; }
				public static function getSubscribedHooks(): array { return ['bootstrap.init' => 20]; }
				public function handle(string $hookName, ...$args) { return 'high'; }
			}
