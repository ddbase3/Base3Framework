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

/**
 * Interface IAccesscontrol
 *
 * Provides access control information related to the current user.
 */
interface IAccesscontrol {

	/**
	 * Returns the ID of the current user.
	 *
	 * @return mixed User ID (type depends on implementation, e.g. int or string)
	 */
	public function getUserId();

	/**
	 * Explicitly triggers the authentication process.
	 *
	 * Used to initialize session/user context before any access is attempted.
	 */
	public function authenticate(): void;
}

