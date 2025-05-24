<?php declare(strict_types=1);

namespace Base3\Dispatcher;

class BootstrapLoggingHookListener implements IHookListener
{
	public function isActive(): bool {
		return false;
	}
    
	// Gibt an, welche Hooks wir abonnieren und mit welcher Priorität
	public static function getSubscribedHooks(): array {
		return [
			'bootstrap.init' => 10,
			'bootstrap.start' => 10,
			'bootstrap.finish' => 10,
		];
	}

	// Callback für jeden Hook
	public function handle(string $hookName, ...$args) {
		error_log("[BootstrapLoggingListener] Hook triggered: {$hookName} at " . date('Y-m-d H:i:s'));
		return true;
	}
}

