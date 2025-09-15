<?php declare(strict_types=1);

namespace Base3\Logger\DatabaseLogger;

use Base3\Logger\Api\ILogger;
use Base3\Database\Api\IDatabase;

class DatabaseLogger implements ILogger {

	private IDatabase $database;

	public function __construct(IDatabase $database) {
		$this->database = $database;
	}

	/**
	 * Write a log entry into the scope table
	 */
	public function log(string $scope, string $log, ?int $timestamp = null): bool {
		if ($timestamp === null) {
			$timestamp = time();
		}

		$table = $this->getTableName($scope);
		$this->ensureTableExists($table);

		$sql = sprintf(
			"INSERT INTO %s (`timestamp`, log) VALUES (%d, '%s')",
			$table,
			$timestamp,
			$this->database->escape($log)
		);

		$this->database->connect();
		$this->database->nonQuery($sql);
		return !$this->database->isError();
	}

	/**
	 * Return all available scopes (logger_* tables)
	 */
	public function getScopes(): array {
		$result = [];
		$this->database->connect();
		$rows = $this->database->multiQuery("SHOW TABLES LIKE 'logger_%'");
		foreach ($rows as $row) {
			$table = reset($row);
			if (preg_match('/^logger_(.+)$/', $table, $m)) {
				$result[] = $m[1];
			}
		}
		return $result;
	}

	public function getNumOfScopes() {
		return count($this->getScopes());
	}

	/**
	 * Return the latest logs for a given scope
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		$table = $this->getTableName($scope);
		$this->ensureTableExists($table);

		$order = $reverse ? "DESC" : "ASC";
		$sql = sprintf(
			"SELECT `timestamp`, log FROM %s ORDER BY id %s LIMIT %d",
			$table,
			$order,
			$num
		);

		$this->database->connect();
		$rows = $this->database->multiQuery($sql);
		$logs = [];
		foreach ($rows as $row) {
			$logs[] = [
				"timestamp" => date("Y-m-d H:i:s", (int)$row['timestamp']),
				"log" => $row['log']
			];
		}
		return $logs;
	}

	// --- private helpers ---

	private function getTableName(string $scope): string {
		// Replace unsafe characters
		$safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $scope);
		return "logger_" . $safe;
	}

	private function ensureTableExists(string $table): void {
		$sql = sprintf(
			"CREATE TABLE IF NOT EXISTS %s (
				id INT AUTO_INCREMENT PRIMARY KEY,
				`timestamp` INT NOT NULL,
				log TEXT NOT NULL
			)",
			$table
		);
		$this->database->connect();
		$this->database->nonQuery($sql);
	}
}

