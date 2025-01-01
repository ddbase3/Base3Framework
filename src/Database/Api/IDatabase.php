<?php declare(strict_types=1);

namespace Database\Api;

interface IDatabase {

	public function connect();
	public function connected();
	public function disconnect();

	public function nonQuery($query);
	public function scalarQuery($query);
	public function singleQuery($query);
	public function &listQuery($query);
	public function &multiQuery($query);
	public function affectedRows();
	public function insertId();
	public function escape($str);

	public function isError();
	public function errorNumber();
	public function errorMessage();
}
