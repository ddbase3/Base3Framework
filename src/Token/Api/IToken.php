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

