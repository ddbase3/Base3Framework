<?php declare(strict_types=1);

namespace Base3\Session\Api;

/**
 * Interface ISession
 *
 * Provides access to session state, identity and lifecycle information.
 */
interface ISession {

	/**
	 * Checks whether the session has already been started.
	 *
	 * @return bool True if the session is active, false otherwise
	 */
	public function started(): bool;

	/**
	 * Returns the unique session identifier.
	 *
	 * @return string The session ID
	 */
	public function getId(): string;

	/**
	 * Starts the session if not already active.
	 *
	 * @return bool True if session was started successfully
	 */
	public function start(): bool;

	/**
	 * Destroys the current session.
	 *
	 * @return bool True if session was destroyed successfully
	 */
	public function destroy(): bool;

	/**
	 * Reads a value from the session.
	 *
	 * @param string $key Session variable name
	 * @param mixed|null $default Default value if not set
	 * @return mixed The stored value or default
	 */
	public function get(string $key, mixed $default = null): mixed;

	/**
	 * Writes a value to the session.
	 *
	 * @param string $key Session variable name
	 * @param mixed $value Value to store
	 * @return void
	 */
	public function set(string $key, mixed $value): void;

	/**
	 * Checks if a value exists in the session.
	 *
	 * @param string $key Session variable name
	 * @return bool True if key exists
	 */
	public function has(string $key): bool;

	/**
	 * Removes a value from the session.
	 *
	 * @param string $key Session variable name
	 * @return void
	 */
	public function remove(string $key): void;
}

