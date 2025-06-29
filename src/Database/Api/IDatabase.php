<?php declare(strict_types=1);

namespace Base3\Database\Api;

interface IDatabase {

	public function connect();
	public function connected();
	public function disconnect();

	/**
	 * Executes a query that modifies data (INSERT, UPDATE, DELETE) without returning a result.
	 */
	public function nonQuery($query);

	/**
	 * Executes a query that returns a single scalar value.
	 */
	public function scalarQuery($query);

	/**
	 * Executes a query that returns a single row as an associative array.
	 */
	public function singleQuery($query);

	/**
	 * Executes a query that returns a list of scalar values (a single column).
	 */
	public function &listQuery($query);

	/**
	 * Executes a query that returns a list of rows (associative arrays).
	 */
	public function &multiQuery($query);

	/**
	 * Returns the number of affected rows from the last operation.
	 */
	public function affectedRows();

	/**
	 * Returns the last inserted ID.
	 */
	public function insertId();

	/**
	 * Escapes a string for use in a query.
	 */
	public function escape($str);

	/**
	 * Returns whether the last operation caused an error.
	 */
	public function isError();

	/**
	 * Returns the error number from the last operation.
	 */
	public function errorNumber();

	/**
	 * Returns the error message from the last operation.
	 */
	public function errorMessage();
}

