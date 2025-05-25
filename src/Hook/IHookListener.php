<?php declare(strict_types=1);

namespace Base3\Hook;

interface IHookListener {

	/**
	 * Only active Listeners are handled.
	 *
	 * @return bool
	 */
	public function isActive(): bool;

	/**
	 * Wird beim Hook-Aufruf aufgerufen.
	 * Die Signatur kannst du je nach Bedarf anpassen.
	 *
	 * @param string $hookName
	 * @param mixed ...$args
	 * @return mixed
	 */
	public function handle(string $hookName, ...$args);

	/**
	 * Liefert eine Liste der Hooks, die der Listener abonniert,
	 * inklusive Prioritäten:
	 *
	 * [
	 *   'hook.name' => int priority,
	 *   'another.hook' => int priority,
	 * ]
	 *
	 * @return array<string,int>
	 */
	public static function getSubscribedHooks(): array;
}

