<?php declare(strict_types=1);

namespace Base3\Session\Api;

/**
 * Interface ISession
 *
 * Provides access to session state and lifecycle information.
 */
interface ISession {

	/**
	 * Checks whether the session has already been started.
	 *
	 * @return bool True if the session is active, false otherwise
	 */
	public function started(): bool;

}

