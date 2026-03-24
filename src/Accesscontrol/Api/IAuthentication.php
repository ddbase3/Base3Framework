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

