<?php declare(strict_types=1);

namespace Base3\Database\Mysql;

use Base3\Core\ServiceLocator;
use Base3\Database\Api\IDatabase;
use Base3\Api\ICheck;
use Base3\Configuration\Api\IConfiguration;

class MysqlDatabase implements IDatabase, ICheck {

	private static $servicelocator;

	private ?\mysqli $connection = null;
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
		if (empty($this->host) || empty($this->user) || empty($this->pass) || empty($this->name)) return;
		$this->connection = new \mysqli($this->host, $this->user, $this->pass, $this->name);
		if ($this->connection->connect_errno) return;
		$this->connection->set_charset('utf8mb4');
		$this->connected = true;
	}

	public function connected(): bool {
		return $this->connected;
	}

	public function disconnect(): void {
		$this->connected = false;
		if ($this->connection) $this->connection->close();
	}

	public function nonQuery(string $query): void {
		$this->connection->query($query);
	}

	public function scalarQuery(string $query): mixed {
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return null;
		if ($row = $result->fetch_array(MYSQLI_NUM)) {
			$result->free();
			return $row[0];
		}
		return null;
	}

	public function singleQuery(string $query): ?array {
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return null;
		if ($row = $result->fetch_assoc()) {
			$result->free();
			return $row;
		}
		return null;
	}

	public function &listQuery(string $query): array {
		$list = [];
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return $list;
		while ($row = $result->fetch_array(MYSQLI_NUM)) $list[] = $row[0];
		$result->free();
		return $list;
	}

	public function &multiQuery(string $query): array {
		$rows = [];
		$result = $this->connection->query($query);
		if (!$result || !$result->num_rows) return $rows;
		while ($row = $result->fetch_assoc()) $rows[] = $row;
		$result->free();
		return $rows;
	}

	public function affectedRows(): int {
		return $this->connection->affected_rows;
	}

	public function insertId(): int|string {
		return $this->connection->insert_id;
	}

	public function escape(string $str): string {
		return $this->connection->real_escape_string($str);
	}

	public function isError(): bool {
		return $this->connection->error !== '';
	}

	public function errorNumber(): int {
		return $this->connection->errno;
	}

	public function errorMessage(): string {
		return $this->connection->error;
	}

	public function checkDependencies() {
		return [
			"mysql_connected" => $this->connect() || $this->connection
				? ($this->connection->connect_errno ? $this->connection->connect_error : "Ok")
				: "Not connected"
		];
	}
}
