<?php declare(strict_types=1);

namespace Base3\State\Database;

use Base3\Database\Api\IDatabase;
use Base3\State\Api\IStateStore;

/**
 * Class DatabaseStateStore
 *
 * Persistent runtime state store backed by a database table.
 *
 * Storage table:
 * - name: base3_statestore
 * - primary key: `key`
 * - value stored as JSON
 * - expires_at used for TTL semantics
 *
 * Notes:
 * - This implementation assumes a MySQL/MariaDB-like dialect for:
 *   - CREATE TABLE IF NOT EXISTS
 *   - INSERT ... ON DUPLICATE KEY UPDATE
 *   - NOW()
 *
 * - connect() is called on every public operation to honor the framework's
 *   lazy-connect expectation (connect only if necessary).
 */
class DatabaseStateStore implements IStateStore {

	private IDatabase $db;
	private string $tableName;
	private bool $initialized = false;

	public function __construct(IDatabase $db, string $tableName = 'base3_statestore') {
		$this->db = $db;
		$this->tableName = $tableName;
	}

	public function get(string $key, mixed $default = null): mixed {
		$this->ensureReady();

		$k = $this->esc($key);
		$row = $this->db->singleQuery(
			"SELECT `value`, `expires_at`
			 FROM `{$this->tableName}`
			 WHERE `key` = '{$k}'
			 LIMIT 1"
		);

		if (!$row) {
			return $default;
		}

		if ($this->isExpiredRow($row)) {
			$this->delete($key);
			return $default;
		}

		return $this->decode($row['value'], $default);
	}

	public function has(string $key): bool {
		$this->ensureReady();

		$k = $this->esc($key);
		$row = $this->db->singleQuery(
			"SELECT `expires_at`
			 FROM `{$this->tableName}`
			 WHERE `key` = '{$k}'
			 LIMIT 1"
		);

		if (!$row) {
			return false;
		}

		if ($this->isExpiredRow($row)) {
			$this->delete($key);
			return false;
		}

		return true;
	}

	public function set(string $key, mixed $value, ?int $ttlSeconds = null): void {
		$this->ensureReady();

		$k = $this->esc($key);
		$v = $this->esc($this->encode($value));
		$expiresSql = $this->expiresSql($ttlSeconds);

		$this->db->nonQuery(
			"INSERT INTO `{$this->tableName}` (`key`, `value`, `updated_at`, `expires_at`)
			 VALUES ('{$k}', '{$v}', NOW(), {$expiresSql})
			 ON DUPLICATE KEY UPDATE
				`value` = VALUES(`value`),
				`updated_at` = NOW(),
				`expires_at` = VALUES(`expires_at`)"
		);
	}

	public function delete(string $key): bool {
		$this->ensureReady();

		$k = $this->esc($key);

		$this->db->nonQuery(
			"DELETE FROM `{$this->tableName}`
			 WHERE `key` = '{$k}'"
		);

		return (int)$this->db->affectedRows() > 0;
	}

	public function setIfNotExists(string $key, mixed $value, ?int $ttlSeconds = null): bool {
		$this->ensureReady();

		$k = $this->esc($key);
		$v = $this->esc($this->encode($value));
		$expiresSql = $this->expiresSql($ttlSeconds);

		/**
		 * Atomic semantics (MySQL/MariaDB):
		 * - If key does not exist: INSERT succeeds (affectedRows = 1)
		 * - If key exists and is NOT expired: UPDATE does nothing (affectedRows = 0)
		 * - If key exists but IS expired: UPDATE refreshes value (affectedRows = 2)
		 *
		 * We update only when expired (or expires_at is set and already past).
		 */
		$this->db->nonQuery(
			"INSERT INTO `{$this->tableName}` (`key`, `value`, `updated_at`, `expires_at`)
			 VALUES ('{$k}', '{$v}', NOW(), {$expiresSql})
			 ON DUPLICATE KEY UPDATE
				`value` = IF(`expires_at` IS NOT NULL AND `expires_at` <= NOW(), VALUES(`value`), `value`),
				`updated_at` = IF(`expires_at` IS NOT NULL AND `expires_at` <= NOW(), NOW(), `updated_at`),
				`expires_at` = IF(`expires_at` IS NOT NULL AND `expires_at` <= NOW(), VALUES(`expires_at`), `expires_at`)"
		);

		$affected = (int)$this->db->affectedRows();

		// 1 = inserted, 2 = updated (expired -> refreshed), 0 = already exists and still valid
		return $affected > 0;
	}

	public function listKeys(string $prefix): array {
		$this->ensureReady();

		$p = $this->esc($prefix);

		// Best-effort: return keys that start with prefix; expired keys may still appear until accessed/cleaned.
		$keys = $this->db->listQuery(
			"SELECT `key`
			 FROM `{$this->tableName}`
			 WHERE `key` LIKE '{$p}%'
			 ORDER BY `key` ASC"
		);

		// Ensure plain PHP array (some implementations return by-ref)
		return is_array($keys) ? $keys : [];
	}

	public function flush(): void {
		// DB backend is immediate; nothing buffered.
	}

	private function ensureReady(): void {
		// Always ensure connection (framework expects lazy connect).
		$this->db->connect();

		if ($this->initialized) {
			return;
		}

		$this->ensureTable();
		$this->initialized = true;
	}

	private function ensureTable(): void {
		/**
		 * Minimal schema:
		 * - `key` as primary key
		 * - `value` as JSON string (TEXT)
		 * - `updated_at` for debugging / admin views
		 * - `expires_at` for TTL (NULL = no expiry)
		 */
		$this->db->nonQuery(
			"CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
				`key` VARCHAR(255) NOT NULL,
				`value` MEDIUMTEXT NOT NULL,
				`updated_at` DATETIME NOT NULL,
				`expires_at` DATETIME NULL,
				PRIMARY KEY (`key`),
				INDEX `idx_expires_at` (`expires_at`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);
	}

	private function isExpiredRow(array $row): bool {
		if (!isset($row['expires_at']) || $row['expires_at'] === null || $row['expires_at'] === '') {
			return false;
		}

		// Compare using DB time format; safest is string->timestamp.
		$ts = strtotime((string)$row['expires_at']);
		if ($ts === false) {
			// If parsing fails, treat as non-expired to avoid accidental data loss.
			return false;
		}

		return $ts <= time();
	}

	private function expiresSql(?int $ttlSeconds): string {
		if ($ttlSeconds === null) {
			return 'NULL';
		}

		if ($ttlSeconds <= 0) {
			// Expire immediately
			return 'NOW()';
		}

		$ttl = (int)$ttlSeconds;
		return "DATE_ADD(NOW(), INTERVAL {$ttl} SECOND)";
	}

	private function encode(mixed $value): string {
		$json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		// If encoding fails, store a structured error marker rather than throwing.
		if ($json === false) {
			return json_encode([
				'__error' => 'json_encode_failed',
				'__type' => gettype($value),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{"__error":"json_encode_failed"}';
		}

		return $json;
	}

	private function decode(string $json, mixed $default): mixed {
		$val = json_decode($json, true);

		// If decoding fails, return default (do not throw in runtime-state plumbing).
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $default;
		}

		return $val;
	}

	private function esc(string $str): string {
		return $this->db->escape($str);
	}
}
