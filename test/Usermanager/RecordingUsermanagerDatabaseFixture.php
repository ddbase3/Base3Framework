<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Database\Api\IDatabase;

/** Records the actual SQL emitted by the owning usermanager and authentication services. */
final class RecordingUsermanagerDatabaseFixture implements IDatabase {
	public int $connections = 0;
	public array $queries = [];
	public array $singleRows = [];
	public array $multiRows = [];
	public array $scalarValues = [];
	public bool $error = false;
	public int $affected = 1;
	public int $inserted = 1;
	private array $listResults = [];
	private array $multiResults = [];

	public function connect(): void { $this->connections++; }
	public function connected(): bool { return $this->connections > 0; }
	public function disconnect(): void { $this->connections = 0; }
	public function beginTransaction(): void { $this->queries[] = 'BEGIN'; }
	public function commit(): void { $this->queries[] = 'COMMIT'; }
	public function rollback(): void { $this->queries[] = 'ROLLBACK'; }
	public function nonQuery(string $query): void { $this->queries[] = $query; }
	public function scalarQuery(string $query): mixed { $this->queries[] = $query; return array_shift($this->scalarValues); }
	public function singleQuery(string $query): ?array { $this->queries[] = $query; return array_shift($this->singleRows); }
	public function &listQuery(string $query): array {
		$this->queries[] = $query;
		$this->listResults = [];
		return $this->listResults;
	}
	public function &multiQuery(string $query): array {
		$this->queries[] = $query;
		$this->multiResults = array_shift($this->multiRows) ?? [];
		return $this->multiResults;
	}
	public function affectedRows(): int { return $this->affected; }
	public function insertId(): int|string { return $this->inserted; }
	public function escape(string $str): string { return str_replace("'", "''", $str); }
	public function isError(): bool { return $this->error; }
	public function errorNumber(): int { return $this->error ? 1 : 0; }
	public function errorMessage(): string { return $this->error ? 'simulated error' : ''; }
}
