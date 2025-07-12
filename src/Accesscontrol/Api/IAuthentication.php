<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Api;

use Base3\Api\IBase;

/**
 * Interface IAuthentication
 *
 * Defines methods for user authentication, session lifecycle, and verbosity control.
 */
interface IAuthentication extends IBase {

	/**
	 * Enables or disables verbose mode (e.g. for logging or debug output).
	 *
	 * @param bool $verbose True to enable verbose mode, false to disable
	 * @return void
	 */
	public function setVerbose($verbose);

	/**
	 * Performs the login operation.
	 *
	 * Typically reads credentials from the request context (e.g. POST, headers, etc.)
	 * and initiates a session if valid.
	 *
	 * @return mixed Implementation-defined result (e.g. user ID, status, or void)
	 */
	public function login();

	/**
	 * Keeps the current session active for the given user ID.
	 *
	 * Useful for session continuation or single sign-on scenarios.
	 *
	 * @param mixed $userid User ID
	 * @return void
	 */
	public function keep($userid);

	/**
	 * Finalizes the login session for the given user ID.
	 *
	 * @param mixed $userid User ID
	 * @return void
	 */
	public function finish($userid);

	/**
	 * Logs the current user out and clears authentication data.
	 *
	 * @return void
	 */
	public function logout();

}

