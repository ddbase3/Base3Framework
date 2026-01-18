<?php declare(strict_types=1);

namespace Base3\Logger\ScopedDatabaseLogger;

use Base3\Logger\AbstractLogger;
use Base3\Database\Api\IDatabase;

/**
 * Class ScopedDatabaseLogger
 *
 * Logs all entries into a single table "base3_log".
 * The logical scope is stored as a column instead of separate tables.
 */
class ScopedDatabaseLogger extends AbstractLogger {

	private IDatabase $database;

	public function __construct(IDatabase $database) {
		$this->database = $database;
	}

	/**
	 * {@inheritdoc}
	 */
	public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
		$scope = (string)($context['scope'] ?? 'default');
		$timestamp = (int)($context['timestamp'] ?? time());

		$this->ensureTableExists();

		$sql = sprintf(
			"INSERT INTO base3_log (`timestamp`, scope, level, log)
			 VALUES (%d, '%s', '%s', '%s')",
			$timestamp,
			$this->database->escape($scope),
			$this->database->escape($level),
			$this->database->escape((string)$message)
		);

		$this->database->connect();
		$this->database->nonQuery($sql);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getScopes(): array {
		$this->ensureTableExists();

		$this->database->connect();
		$rows = $this->database->multiQuery(
			"SELECT DISTINCT scope FROM base3_log ORDER BY scope ASC"
		);

		$result = [];
		foreach ($rows as $row) {
			if (isset($row['scope'])) {
				$result[] = (string)$row['scope'];
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
		$this->ensureTableExists();

		$order = $reverse ? "DESC" : "ASC";

		$sql = sprintf(
			"SELECT `timestamp`, scope, level, log
			 FROM base3_log
			 WHERE scope = '%s'
			 ORDER BY id %s
			 LIMIT %d",
			$this->database->escape($scope),
			$order,
			$num
		);

		$this->database->connect();
		$rows = $this->database->multiQuery($sql);

		$logs = [];
		foreach ($rows as $row) {
			$logs[] = [
				"timestamp" => date("Y-m-d H:i:s", (int)$row['timestamp']),
				"scope"     => (string)$row['scope'],
				"level"     => (string)$row['level'],
				"log"       => (string)$row['log']
			];
		}

		return $logs;
	}

	// -------------------------------------------------
	// internals
	// -------------------------------------------------

	private function ensureTableExists(): void {
		$sql = "
			CREATE TABLE IF NOT EXISTS base3_log (
				id INT AUTO_INCREMENT PRIMARY KEY,
				`timestamp` INT NOT NULL,
				scope VARCHAR(190) NOT NULL,
				level VARCHAR(20) NOT NULL,
				log TEXT NOT NULL,
				INDEX idx_scope (scope),
				INDEX idx_timestamp (`timestamp`)
			)
		";

		$this->database->connect();
		$this->database->nonQuery($sql);
	}
}
