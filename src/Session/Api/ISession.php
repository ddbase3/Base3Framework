<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

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

