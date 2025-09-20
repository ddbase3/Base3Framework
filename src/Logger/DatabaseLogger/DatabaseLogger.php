<?php declare(strict_types=1);

namespace Base3\Logger\DatabaseLogger;

use Base3\Logger\AbstractLogger;
use Base3\Database\Api\IDatabase;

/**
 * Class DatabaseLogger
 *
 * Database-backed logger implementation.
 * Stores logs in per-scope tables prefixed with "logger_".
 */
class DatabaseLogger extends AbstractLogger {

	private IDatabase $database;

	public function __construct(IDatabase $database) {
		$this->database = $database;
	}

	/**
	 * Implementation of logLevel() from AbstractLogger.
	 *
	 * @param string $level One of the ILogger::* constants
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data (must contain "scope" and "timestamp")
	 * @return void
	 */
	public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
		$scope = $context['scope'] ?? 'default';
		$timestamp = $context['timestamp'] ?? time();

		$table = $this->getTableName($scope);
		$this->ensureTableExists($table);

		$sql = sprintf(
			"INSERT INTO %s (`timestamp`, level, log) VALUES (%d, '%s', '%s')",
			$table,
			$timestamp,
			$this->database->escape($level),
			$this->database->escape((string) $message)
		);

		$this->database->connect();
		$this->database->nonQuery($sql);
	}

	/**
	 * {@inheritdoc}
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

	/**
	 * {@inheritdoc}
	 */
	public function getNumOfScopes() {
		return count($this->getScopes());
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		$table = $this->getTableName($scope);
		$this->ensureTableExists($table);

		$order = $reverse ? "DESC" : "ASC";
		$sql = sprintf(
			"SELECT `timestamp`, level, log FROM %s ORDER BY id %s LIMIT %d",
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
				"level"     => $row['level'],
				"log"       => $row['log']
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
				level VARCHAR(20) NOT NULL,
				log TEXT NOT NULL
			)",
			$table
		);
		$this->database->connect();
		$this->database->nonQuery($sql);
	}
}

