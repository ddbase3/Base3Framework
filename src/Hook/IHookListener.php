<?php declare(strict_types=1);

namespace Base3\Hook;

/**
 * Interface IHookListener
 *
 * Represents a listener that can subscribe to and handle named hooks.
 */
interface IHookListener {

	/**
	 * Determines whether the listener is currently active.
	 *
	 * Only active listeners are invoked.
	 *
	 * @return bool True if the listener is active, false otherwise
	 */
	public function isActive(): bool;

	/**
	 * Called when a hook is triggered.
	 *
	 * Signature can be adjusted to your specific needs.
	 *
	 * @param string $hookName Name of the triggered hook
	 * @param mixed ...$args Optional additional hook arguments
	 * @return mixed Result of hook handling
	 */
	public function handle(string $hookName, ...$args);

	/**
	 * Returns a list of hook names the listener subscribes to, with priorities.
	 *
	 * Example:
	 * [
	 *   'hook.name' => 10,
	 *   'another.hook' => 0,
	 * ]
	 *
	 * @return array<string, int> Associative array of hook names and their priority
	 */
	public static function getSubscribedHooks(): array;

}

