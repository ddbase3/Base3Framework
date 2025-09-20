<?php declare(strict_types=1);

namespace Base3\Logger\FileLogger;

use Base3\Logger\AbstractLogger;

/**
 * Class FileLogger
 *
 * Simple file-based logger implementation.
 * Stores logs in per-scope files under the FileLogger directory.
 */
class FileLogger extends AbstractLogger {

	private string $dir = DIR_LOCAL;

	public function __construct() {
		$this->dir = rtrim($this->dir, DIRECTORY_SEPARATOR);
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

		$dir = $this->dir . DIRECTORY_SEPARATOR . "FileLogger";
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		$file = $dir . DIRECTORY_SEPARATOR . $scope . ".log";
		$fp = fopen($file, "a");

		// Example line format: 2025-09-20 18:30:00; [info]; message text
		$line = date("Y-m-d H:i:s", $timestamp)
			. "; [" . strtoupper($level) . "]; "
			. (string) $message
			. "\n";

		fwrite($fp, $line);
		fclose($fp);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getScopes(): array {
		$dir = $this->dir . DIRECTORY_SEPARATOR . "FileLogger";
		if (!is_dir($dir)) {
			return [];
		}

		$scopes = [];
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry === "." || $entry === "..") continue;
				if (is_dir($dir . DIRECTORY_SEPARATOR . $entry)) continue;
				if (substr($entry, -4) !== ".log") continue;

				$scopes[] = substr($entry, 0, -4);
			}
			closedir($handle);
		}
		return $scopes;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNumOfScopes() {
		return sizeof($this->getScopes());
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		$dir = $this->dir . DIRECTORY_SEPARATOR . "FileLogger";
		$file = $dir . DIRECTORY_SEPARATOR . $scope . ".log";

		$str = $this->tail($file, $num);
		$lines = explode("\n", $str);
		$logs = [];

		foreach ($lines as $line) {
			if (trim($line) === '') continue;

			// Parse: "2025-09-20 18:30:00; [INFO]; message"
			$parts = explode("; ", $line, 3);
			$logs[] = [
				"timestamp" => $parts[0] ?? '',
				"level"     => trim($parts[1] ?? '', "[]"),
				"log"       => $parts[2] ?? ''
			];
		}

		return $reverse ? array_reverse($logs) : $logs;
	}

	// -----------------------------------------------------
	// private helpers
	// -----------------------------------------------------

	/**
	 * Tail helper: reads the last N lines of a file efficiently.
	 *
	 * @param string $filepath File path
	 * @param int $lines Number of lines to read
	 * @param bool $adaptive Adjust buffer size based on requested lines
	 * @return string The last N lines joined by newline
	 */
	private function tail(string $filepath, int $lines = 1, bool $adaptive = true): string {
		if (!file_exists($filepath)) return '';

		$f = @fopen($filepath, "rb");
		if ($f === false) return '';

		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		fseek($f, -1, SEEK_END);
		if (fread($f, 1) !== "\n") $lines -= 1;

		$output = '';
		$chunk = '';

		while (ftell($f) > 0 && $lines >= 0) {
			$seek = min(ftell($f), $buffer);
			fseek($f, -$seek, SEEK_CUR);
			$output = ($chunk = fread($f, $seek)) . $output;
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			$lines -= substr_count($chunk, "\n");
		}

		while ($lines++ < 0) {
			$output = substr($output, strpos($output, "\n") + 1);
		}

		fclose($f);
		return trim($output);
	}
}

