<?php declare(strict_types=1);

namespace Base3\Usermanager;

use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\User;

/**
 * Proxy usermanager with request-lifetime caching.
 *
 * Fixes performance issues where each getUser() call triggers
 * a microservice HTTP request (~120ms).
 */
class UsermanagerProxy implements IUsermanager {

	private $connector;

	/** @var User|null */
	private ?User $cachedUser = null;

	/** @var User[]|null */
	private ?array $cachedGroups = null;

	/** @var array|null */
	private ?array $cachedAllUsers = null;

	public function __construct($connector) {
		$this->connector = $connector;
	}

	/**
	 * Returns the current user (cached).
	 */
	public function getUser() {
		if ($this->cachedUser !== null) {
			return $this->cachedUser;
		}

		$user = $this->connector->getUser();
		$this->cachedUser = is_array($user) ? User::fromArray($user) : $user;

		return $this->cachedUser;
	}

	/**
	 * Returns groups (cached).
	 */
	public function getGroups() {
		if ($this->cachedGroups !== null) {
			return $this->cachedGroups;
		}

		$groups = $this->connector->getGroups();

		if (is_array($groups) && isset($groups[0]) && is_array($groups[0])) {
			$this->cachedGroups = array_map(fn($g) => User::fromArray($g), $groups);
		} else {
			$this->cachedGroups = $groups;
		}

		return $this->cachedGroups;
	}

	public function registUser($userid, $password, $data = null) {
		return $this->connector->registUser($userid, $password, $data);
	}

	public function changePassword($oldpassword, $newpassword) {
		return $this->connector->changePassword($oldpassword, $newpassword);
	}

	/**
	 * Returns all users (cached).
	 */
	public function getAllUsers() {
		if ($this->cachedAllUsers !== null) {
			return $this->cachedAllUsers;
		}

		$users = $this->connector->getAllUsers();

		if (is_array($users) && isset($users[0]) && is_array($users[0])) {
			$this->cachedAllUsers = array_map(fn($u) => User::fromArray($u), $users);
		} else {
			$this->cachedAllUsers = $users;
		}

		return $this->cachedAllUsers;
	}
}

