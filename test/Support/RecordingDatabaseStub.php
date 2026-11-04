<?php declare(strict_types=1);

namespace Base3\Test\Support;

use Base3\Database\Api\IDatabase;

/** Records the SQL boundary without implementing an alternate persistence layer. */
final class RecordingDatabaseStub implements IDatabase {

	public array $nonQueries = [];
	public array $singleQueries = [];
	public array $scalarQueries = [];
	public array $multiQueries = [];
	public ?array $singleRow = null;
	public mixed $scalarValue = null;
	public array $multiRows = [];
	public array $listRows = [];
	public bool $connectedFlag = true;
	public bool $error = false;
	public int $errorCode = 0;
	public string $errorText = '';
	public int $connectCalls = 0;

	public function connect(): void { $this->connectCalls++; }
	public function connected(): bool { return $this->connectedFlag; }
	public function disconnect(): void { $this->connectedFlag=false; }
	public function beginTransaction(): void {}
	public function commit(): void {}
	public function rollback(): void {}
	public function nonQuery(string $query): void { $this->nonQueries[]=$query; }
	public function scalarQuery(string $query): mixed { $this->scalarQueries[]=$query; return $this->scalarValue; }
	public function singleQuery(string $query): ?array { $this->singleQueries[]=$query; return $this->singleRow; }
	public function &listQuery(string $query): array { return $this->listRows; }
	public function &multiQuery(string $query): array { $this->multiQueries[]=$query; return $this->multiRows; }
	public function affectedRows(): int { return 1; }
	public function insertId(): int|string { return 1; }
	public function escape(string $str): string { return str_replace("'","\\'",$str); }
	public function isError(): bool { return $this->error; }
	public function errorNumber(): int { return $this->errorCode; }
	public function errorMessage(): string { return $this->errorText; }
}
