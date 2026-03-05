<?php declare(strict_types=1);

namespace Base3\Database\Postgres;

use Base3\Api\ICheck;
use Base3\Configuration\ArrayConfiguration;
use Base3\Configuration\Api\IConfiguration;
use Base3\Core\ServiceLocator;
use Base3\Database\Api\IDatabase;

class PostgresDatabase implements IDatabase, ICheck {

	private static $servicelocator;

	/**
	 * pg_connect returns a PgSql\Connection object in newer PHP versions,
	 * but historically it was a resource. Keep it broad.
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

		$data = [];
		if (is_array($cnf)) {
			$data = isset($cnf['database']) && is_array($cnf['database'])
				? $cnf
				: ['database' => $cnf];
		}

		return new self(new ArrayConfiguration($data));
	}

	public function connect(): void {
		if ($this->connected) return;
		if (empty($this->host) || empty($this->user) || empty($this->pass) || empty($this->name)) return;

		$dsn = "host={$this->host} dbname={$this->name} user={$this->user} password={$this->pass}";
		$this->connection = pg_connect($dsn);

		if ($this->connection === false) {
			$this->connection = null;
			return;
		}

		$this->connected = true;
	}

	public function connected(): bool {
		return $this->connected;
	}

	public function disconnect(): void {
		if ($this->connection) {
			@pg_close($this->connection);
		}
		$this->connection = null;
		$this->connected = false;
	}

	public function beginTransaction(): void {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$result = pg_query($this->connection, "BEGIN");
		if ($result === false) {
			throw new \RuntimeException("Failed to begin transaction: " . (string)pg_last_error($this->connection));
		}
		pg_free_result($result);
	}

	public function commit(): void {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$result = pg_query($this->connection, "COMMIT");
		if ($result === false) {
			throw new \RuntimeException("Failed to commit transaction: " . (string)pg_last_error($this->connection));
		}
		pg_free_result($result);
	}

	public function rollback(): void {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$result = pg_query($this->connection, "ROLLBACK");
		if ($result === false) {
			throw new \RuntimeException("Failed to rollback transaction: " . (string)pg_last_error($this->connection));
		}
		pg_free_result($result);
	}

	public function nonQuery(string $query): void {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$result = pg_query($this->connection, $query);
		if ($result === false) {
			throw new \RuntimeException("PostgreSQL nonQuery failed: " . (string)pg_last_error($this->connection));
		}
		pg_free_result($result);
	}

	public function scalarQuery(string $query): mixed {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$result = pg_query($this->connection, $query);
		if ($result === false) {
			throw new \RuntimeException("PostgreSQL scalarQuery failed: " . (string)pg_last_error($this->connection));
		}

		if (pg_num_rows($result) === 0) {
			pg_free_result($result);
			return null;
		}

		$row = pg_fetch_row($result);
		pg_free_result($result);

		return $row[0] ?? null;
	}

	public function singleQuery(string $query): ?array {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$result = pg_query($this->connection, $query);
		if ($result === false) {
			throw new \RuntimeException("PostgreSQL singleQuery failed: " . (string)pg_last_error($this->connection));
		}

		if (pg_num_rows($result) === 0) {
			pg_free_result($result);
			return null;
		}

		$row = pg_fetch_assoc($result);
		pg_free_result($result);

		return $row ?: null;
	}

	public function &listQuery(string $query): array {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$list = [];
		$result = pg_query($this->connection, $query);

		if ($result === false) {
			throw new \RuntimeException("PostgreSQL listQuery failed: " . (string)pg_last_error($this->connection));
		}

		while ($row = pg_fetch_row($result)) {
			$list[] = $row[0];
		}

		pg_free_result($result);
		return $list;
	}

	public function &multiQuery(string $query): array {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		$rows = [];
		$result = pg_query($this->connection, $query);

		if ($result === false) {
			throw new \RuntimeException("PostgreSQL multiQuery failed: " . (string)pg_last_error($this->connection));
		}

		while ($row = pg_fetch_assoc($result)) {
			$rows[] = $row;
		}

		pg_free_result($result);
		return $rows;
	}

	public function affectedRows(): int {
		// pg_affected_rows expects a result resource; this legacy API can't provide "last affected rows" reliably.
		// We keep a safe, deterministic behavior.
		return 0;
	}

	public function insertId(): int|string {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		// LASTVAL() works only if a sequence was used in this session.
		$result = pg_query($this->connection, "SELECT LASTVAL()");
		if ($result === false) {
			return 0;
		}

		$row = pg_fetch_row($result);
		pg_free_result($result);

		$val = $row[0] ?? 0;
		if (is_string($val) && ctype_digit($val)) return (int)$val;
		return $val;
	}

	public function escape(string $str): string {
		$this->connect();
		if (!$this->connection) throw new \RuntimeException("PostgreSQL connection not available.");

		return pg_escape_string($this->connection, $str);
	}

	public function isError(): bool {
		if (!$this->connection) return true;
		return pg_last_error($this->connection) !== "";
	}

	public function errorNumber(): int {
		// Not available via pg_* in this implementation.
		return 0;
	}

	public function errorMessage(): string {
		if (!$this->connection) return "Not connected";
		return (string)pg_last_error($this->connection);
	}

	public function checkDependencies() {
		if (!self::$servicelocator) self::$servicelocator = ServiceLocator::getInstance();

		// Don't call connect() here (it returns void). Just check current state / last error.
		$msg = "Not connected";
		if ($this->connection) {
			$msg = pg_last_error($this->connection) ?: "Ok";
		}

		return [
			"depending_services" => self::$servicelocator->get('configuration') == null ? "Fail" : "Ok",
			"postgres_connected" => $msg
		];
	}
}
