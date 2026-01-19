<?php declare(strict_types=1);

namespace Base3\Logger\ScopedDatabaseLogger;

use Base3\Logger\AbstractLogger;
use Base3\Database\Api\IDatabase;

/**
 * Class ScopedDatabaseLogger
 *
 * Logs all entries into a single table "base3_log".
 * The logical scope is stored as a column instead of separate tables.
 * Timestamp is stored as DATETIME (Y-m-d H:i:s).
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

                // allow explicit timestamp, otherwise now()
                $timestamp = $context['timestamp'] ?? date('Y-m-d H:i:s');

                // normalize if unix timestamp was passed accidentally
                if (is_int($timestamp)) {
                        $timestamp = date('Y-m-d H:i:s', $timestamp);
                }

                $this->ensureTableExists();

                $sql = sprintf(
                        "INSERT INTO base3_log (`timestamp`, scope, level, log)
                         VALUES ('%s', '%s', '%s', '%s')",
                        $this->database->escape((string)$timestamp),
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
                                "timestamp" => (string)$row['timestamp'],
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
                                `timestamp` DATETIME NOT NULL,
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
