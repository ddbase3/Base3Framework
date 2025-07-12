<?php declare(strict_types=1);

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

