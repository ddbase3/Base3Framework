<?php declare(strict_types=1);

namespace Base3\Usermanager\Api;

/**
 * Interface IUsermanager
 *
 * Defines user and group management operations including authentication, registration, and lookup.
 */
interface IUsermanager {

	/**
	 * Returns the current user data or identifier.
	 *
	 * @return mixed User object, array, or ID depending on implementation
	 */
	public function getUser();

	/**
	 * Returns the groups associated with the current user.
	 *
	 * @return array List of group identifiers or group data
	 */
	public function getGroups();

	/**
	 * Registers a new user with the given ID, password, and optional additional data.
	 *
	 * @param string|int $userid Unique user ID
	 * @param string $password Plaintext password
	 * @param mixed|null $data Optional additional user data (e.g. array or object)
	 * @return bool True on success, false on failure
	 */
	public function registUser($userid, $password, $data = null);

	/**
	 * Changes the password for the current user.
	 *
	 * @param string $oldpassword Current password
	 * @param string $newpassword New password to set
	 * @return bool True if the password was changed successfully
	 */
	public function changePassword($oldpassword, $newpassword);

	/**
	 * Returns a list of all registered users.
	 *
	 * @return array List of users (format depends on implementation)
	 */
	public function getAllUsers();

}

