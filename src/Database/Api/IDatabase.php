<?php declare(strict_types=1);

namespace Base3\Database\Api;

interface IDatabase {

	public function connect();
	public function connected();
	public function disconnect();

	/**
	 * inserts, updates, deletes without response
	 */
	public function nonQuery($query);

	/**
	 * response is just a single value
	 */
	public function scalarQuery($query);

	/**
	 * response is just a single row
	 */
	public function singleQuery($query);

	/**
	 * response is a list of value (a column)
	 */
	public function &listQuery($query);

	/**
	 * response is a list of rows
	 */
	public function &multiQuery($query);

	public function affectedRows();
	public function insertId();
	public function escape($str);

	public function isError();
	public function errorNumber();
	public function errorMessage();
}
