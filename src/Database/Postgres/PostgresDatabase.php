<?php declare(strict_types=1);

namespace Base3\Database\Postgres;

use Base3\Core\ServiceLocator;
use Base3\Database\Api\IDatabase;
use Base3\Api\ICheck;

class PostgresDatabase implements IDatabase, ICheck {

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

	public static function getInstance($cnf = null) {

		if ($cnf == null) {
			self::$servicelocator = ServiceLocator::getInstance();
			$configuration = self::$servicelocator->get('configuration');
			if ($configuration != null) $cnf = $configuration->get('database');
		}

		if (!isset(self::$instance)) self::$instance = $cnf == null
			? new PostgresDatabase(null, null, null, null)
			: new PostgresDatabase($cnf["host"], $cnf["user"], $cnf["pass"], $cnf["name"]);
		return self::$instance;
	}

	public function connect() {
		if ($this->connected) return;
		$dsn = "host={$this->host} dbname={$this->name} user={$this->user} password={$this->pass}";
		$this->connection = pg_connect($dsn);
		if ($this->connection === false) return;
		$this->connected = true;
	}

	public function connected() {
		return !!$this->connected;
	}

	public function disconnect() {
		if ($this->connected && $this->connection) {
			pg_close($this->connection);
			$this->connected = false;
		}
	}

	public function nonQuery($query) {
		pg_query($this->connection, $query);
	}

	public function scalarQuery($query) {
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return null;
		$row = pg_fetch_row($result);
		pg_free_result($result);
		return $row[0] ?? null;
	}

	public function singleQuery($query) {
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return null;
		$row = pg_fetch_assoc($result);
		pg_free_result($result);
		return $row;
	}

	public function &listQuery($query) {
		$list = array();
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return $list;
		while ($row = pg_fetch_row($result)) $list[] = $row[0];
		pg_free_result($result);
		return $list;
	}

	public function &multiQuery($query) {
		$rows = array();
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return $rows;
		while ($row = pg_fetch_assoc($result)) $rows[] = $row;
		pg_free_result($result);
		return $rows;
	}

	public function affectedRows() {
		// Letztes Resultat muss gespeichert werden
		return pg_affected_rows($this->connection);
	}

	public function insertId() {
		// Funktioniert nur, wenn das INSERT RETURNING id enthält
		// Alternative: currval('sequence_name')
		$result = pg_query($this->connection, "SELECT LASTVAL()");
		if (!$result) return null;
		$row = pg_fetch_row($result);
		pg_free_result($result);
		return $row[0] ?? null;
	}

	public function escape($str) {
		return pg_escape_string($this->connection, $str);
	}

	public function isError() {
		// Keine native Error-Funktion, aber man kann letzten Fehler speichern
		return pg_last_error($this->connection) !== "";
	}

	public function errorNumber() {
		// PostgreSQL gibt keine Fehlernummern über pg_* zurück
		return 0;
	}

	public function errorMessage() {
		return pg_last_error($this->connection);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => self::$servicelocator->get('configuration') == null ? "Fail" : "Ok",
			"postgres_connected" => $this->connect() || $this->connection ? (pg_last_error($this->connection) ?: "Ok") : "Not connected"
		);
	}
}

