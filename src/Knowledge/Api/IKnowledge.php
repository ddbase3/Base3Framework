<?php declare(strict_types=1);

namespace Base3\Knowledge\Api;

/**
 * Interface IKnowledge
 *
 * Provides access to structured knowledge scopes, their fields, and associated data.
 */
interface IKnowledge {

	/**
	 * Returns a list of available knowledge scopes.
	 *
	 * @return array<string> List of scope identifiers
	 */
	public function getScopes();

	/**
	 * Returns the available fields for a given scope.
	 *
	 * @param string $scope The name of the scope
	 * @return array<string, string> List of field keys and their labels
	 */
	public function getFields($scope);

	/**
	 * Returns data for a given scope and selected fields.
	 *
	 * @param string $scope The name of the scope
	 * @param array<string>|null $fields Optional list of field keys to return (null = all)
	 * @return array<int, array<string, mixed>> Array of result rows with field values
	 */
	public function getData($scope, $fields = null);

}

