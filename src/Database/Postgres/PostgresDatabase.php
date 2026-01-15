<?php declare(strict_types=1);

namespace Base3\Database\Postgres;

use Base3\Core\ServiceLocator;
use Base3\Database\Api\IDatabase;
use Base3\Api\ICheck;
use Base3\Configuration\Api\IConfiguration;

class PostgresDatabase implements IDatabase, ICheck {

	private static $servicelocator;

	/**
	 * pg_connect returns a PgSql\Connection object in newer PHP versions,
	 * but historically it was a resource.
	 * We keep it broad to avoid runtime breaks across environments.
	 */
	private mixed $connection = null;
	private bool $connected = false;

	private ?string $host;
	private ?string $user;
	private ?string $pass;
	private ?string $name;

	public function __construct(IConfiguration $config) {
		$cnf = $config->get('database');
		$this->host = $cnf['host'] ?? null;
		$this->user = $cnf['user'] ?? null;
		$this->pass = $cnf['pass'] ?? null;
		$this->name = $cnf['name'] ?? null;
	}

	public static function getInstance($cnf = null): self {
		if ($cnf === null) {
			if (!self::$servicelocator) self::$servicelocator = ServiceLocator::getInstance();
			$configuration = self::$servicelocator->get('configuration');
			if ($configuration !== null) {
				return new self($configuration);
			}
		}

		return new self(new class($cnf) implements IConfiguration {
			private $cnf;
			public function __construct($cnf) { $this->cnf = $cnf; }
			public function get($configuration = "") { return $this->cnf; }
			public function set($data, $configuration = "") {}
			public function save() {}
		});
	}

	public function connect(): void {
		if ($this->connected) return;
		$dsn = "host={$this->host} dbname={$this->name} user={$this->user} password={$this->pass}";
		$this->connection = pg_connect($dsn);
		if ($this->connection === false) return;
		$this->connected = true;
	}

	public function connected(): bool {
		return $this->connected;
	}

	public function disconnect(): void {
		if ($this->connected && $this->connection) {
			pg_close($this->connection);
			$this->connected = false;
		}
	}

	public function nonQuery(string $query): void {
		pg_query($this->connection, $query);
	}

	public function scalarQuery(string $query): mixed {
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return null;
		$row = pg_fetch_row($result);
		pg_free_result($result);
		return $row[0] ?? null;
	}

	public function singleQuery(string $query): ?array {
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return null;
		$row = pg_fetch_assoc($result);
		pg_free_result($result);
		return $row ?: null;
	}

	public function &listQuery(string $query): array {
		$list = [];
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return $list;
		while ($row = pg_fetch_row($result)) $list[] = $row[0];
		pg_free_result($result);
		return $list;
	}

	public function &multiQuery(string $query): array {
		$rows = [];
		$result = pg_query($this->connection, $query);
		if (!$result || pg_num_rows($result) == 0) return $rows;
		while ($row = pg_fetch_assoc($result)) $rows[] = $row;
		pg_free_result($result);
		return $rows;
	}

	public function affectedRows(): int {
		return (int)pg_affected_rows($this->connection);
	}

	public function insertId(): int|string {
		$result = pg_query($this->connection, "SELECT LASTVAL()");
		if (!$result) return 0;
		$row = pg_fetch_row($result);
		pg_free_result($result);

		// LASTVAL() returns text; cast to int if numeric, otherwise return string
		$val = $row[0] ?? 0;
		if (is_string($val) && ctype_digit($val)) return (int)$val;
		return $val;
	}

	public function escape(string $str): string {
		return pg_escape_string($this->connection, $str);
	}

	public function isError(): bool {
		return pg_last_error($this->connection) !== "";
	}

	public function errorNumber(): int {
		return 0; // PostgreSQL liefert hier in dieser Implementierung keine Fehlernummern
	}

	public function errorMessage(): string {
		return (string)pg_last_error($this->connection);
	}

	public function checkDependencies() {
		return [
			"depending_services" => self::$servicelocator->get('configuration') == null ? "Fail" : "Ok",
			"postgres_connected" => $this->connect() || $this->connection ? (pg_last_error($this->connection) ?: "Ok") : "Not connected"
		];
	}
}
