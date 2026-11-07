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
	 * Returns the authenticated identity key used by the active host usermanager.
	 * This is NOT necessarily the native database primary key User::$id.
	 * In a Base3SystemUsermanager deployment this is the current exact login
	 * User::$userid (base3system_sysuser.name), including numeric-looking logins.
	 * KeyHarbor credentials instead store the host-native User::$id; authentication
	 * providers MUST resolve it through the native usermanager before returning
	 * a host login key. No numeric-string heuristic or implicit cross-host mapping
	 * is permitted. A missing or deleted user must not become an authenticated actor.
	 *
	 * @return mixed Exact host authentication key, or host-specific unauthenticated result.
	 * @throws \Throwable On errors in underlying authentication or user lookup.
	 */
	public function getUserId();

	/**
	 * Explicitly triggers the authentication process.
	 *
	 * Used to initialize session/user context before any access is attempted.
	 */
	public function authenticate(): void;
}

