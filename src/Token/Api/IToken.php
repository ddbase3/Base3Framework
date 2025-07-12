<?php declare(strict_types=1);

namespace Base3\Token\Api;

/**
 * Interface IToken
 *
 * Provides methods for creating, validating, and managing scoped tokens.
 */
interface IToken {

	/**
	 * Creates a new token for the given scope and ID.
	 *
	 * @param string $scope Logical scope (e.g. "auth", "reset", "api")
	 * @param string|int $id Entity identifier (e.g. user ID)
	 * @param int $size Token length in bytes (default: 32)
	 * @param int $duration Token validity duration in seconds (default: 3600)
	 * @return string Generated token string
	 */
	public function create($scope, $id, $size = 32, $duration = 3600);

	/**
	 * Validates a token for the given scope and ID.
	 *
	 * @param string $scope Logical scope
	 * @param string|int $id Entity identifier
	 * @param string $token Token string to check
	 * @return bool True if token is valid, false otherwise
	 */
	public function check($scope, $id, $token);

	/**
	 * Deletes a specific token for the given scope and ID.
	 *
	 * @param string $scope Logical scope
	 * @param string|int $id Entity identifier
	 * @param string $token Token string to delete
	 * @return void
	 */
	public function delete($scope, $id, $token);

	/**
	 * Removes all tokens for the given scope and ID.
	 *
	 * @param string $scope Logical scope
	 * @param string|int $id Entity identifier
	 * @return void
	 */
	public function clean($scope, $id);

}

