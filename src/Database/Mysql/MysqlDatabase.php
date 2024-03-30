<?php

namespace Database\Mysql;

use Database\Api\IDatabase;
use Api\ICheck;

class MysqlDatabase implements IDatabase, ICheck {

	private static $servicelocator;

	private $connection;
	private static $instance;

	private $connected = false;
	private $host;
	private $user;
	private $pass;
	private $name;

	private function __construct($host, $user, $pass, $name) {
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->name = $name;
	}

	private function __clone() {}
	private function __wakeup() {}

	public static function getInstance($cnf = null) {

		if ($cnf == null) {
			self::$servicelocator = \Base3\ServiceLocator::getInstance();
			$configuration = self::$servicelocator->get('configuration');
			if ($configuration != null) $cnf = $configuration->get('database');
		}

		if (!isset(self::$instance)) self::$instance = $cnf == null
			? new MysqlDatabase(null, null, null, null)
			: new MysqlDatabase($cnf["host"], $cnf["user"], $cnf["pass"], $cnf["name"]);
		return self::$instance;
	}

	public function connect() {
		if ($this->connected) return;
		$this->connection = new \mysqli($this->host, $this->user, $this->pass, $this->name);
		if ($this->connection->connect_errno) return;
		$this->connection->set_charset("utf8");
		$this->connected = true;
	}

	public function connected() {
		return !!$this->connected;
	}

	public function disconnect() {
		$this->connected = false;
		$this->connection->close();
	}

	public function nonQuery($query) {
		$this->connection->query($query);
	}

	public function scalarQuery($query) {
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return null;
		if ($row = $result->fetch_array(MYSQLI_NUM)) {
			$result->free();
			return $row[0];
		}
		return null;
	}

	public function singleQuery($query) {
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return null;
		if ($row = $result->fetch_assoc()) {
			$result->free();
			return $row;
		}
		return null;
	}

	public function &listQuery($query) {
		$list = array();
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return $list;
		while ($row = $result->fetch_array(MYSQLI_NUM)) $list[] = $row[0];
		$result->free();
		return $list;
	}

	public function &multiQuery($query) {
		$rows = array();
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return $rows;
		while ($row = $result->fetch_assoc()) $rows[] = $row;
		$result->free();
		return $rows;
	}

	public function affectedRows() {
		return $this->connection->affected_rows;
	}

	public function insertId() {
		return $this->connection->insert_id;
	}

	public function escape($str) {
		return $this->connection->real_escape_string($str);
	}

	public function isError() {
		return $this->connection->error != 0;
	}

	public function errorNumber() {
		return $this->connection->errno;
	}

	public function errorMessage() {
		return $this->connection->error;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => self::$servicelocator->get('configuration') == null ? "Fail" : "Ok",
			"mysql_connected" => $this->connect() || $this->connection ? ($this->connection->connect_errno ? $this->connection->connect_error : "Ok") : "Not connected"
		);
	}

}
