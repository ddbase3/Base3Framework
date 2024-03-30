<?php

namespace Logger\FileLogger;

use Logger\Api\ILogger;

class FileLogger implements ILogger {

	private $dir = DIR_LOCAL;

	public function __construct() {
		$this->dir = rtrim($this->dir, DIRECTORY_SEPARATOR);
	}

	// Implementation of ILogger

	// MicroService: (später, abgeleitet von dieser Klasse, z.B. als "class FileLoggerService")
	// Parameter timestamp ist optional, alternativ wird der aktuelle Server-Timestamp verwendet
	// Beispiel: http://www.base3.de/base3xrmlogger/filelogger.json?call=log&params[scope]=test&params[log]=Blub&params[timestamp]=1566282924
	// Beispiel: php index.php name=filelogger out=json call=log params[scope]=test params[log]=EinTestLog params[timestamp]=1566282924
	public function log($scope, $log, $timestamp = null) {
		if ($timestamp == null) $timestamp = time();
		$dir = $this->dir . DIRECTORY_SEPARATOR . "FileLogger";
		if (!is_dir($dir)) mkdir($dir, 0777, true);
		$fp = fopen($dir . DIRECTORY_SEPARATOR . $scope . ".log", "a");
		fwrite($fp, date("Y-m-d H:i:s", $timestamp) . "; " . $log . "\n");
		fclose($fp);
		return true;
	}

	public function getScopes() {
		$dir = $this->dir . DIRECTORY_SEPARATOR . "FileLogger";
		if ($handle = opendir($dir)) {
			$scopes = array();
			while (false !== ($entry = readdir($handle))) {
				if ($entry == "." || $entry == ".." || is_dir($dir . DIRECTORY_SEPARATOR . $entry) || substr($entry, -4) != ".log") continue;
				$scopes[] = substr($entry, 0, -4);
			}
			closedir($handle);
			return $scopes;
		}
		return array();
	}

	public function getNumOfScopes() {
		return sizeof($this->getScopes());
	}

	public function getLogs($scope, $num = 50, $reverse = true) {
		$logs = array();
		$dir = $this->dir . DIRECTORY_SEPARATOR . "FileLogger";
		$file = $dir . DIRECTORY_SEPARATOR . $scope . ".log";
		$str = $this->tail($file, $num);
		$lines = explode("\n", $str);
		foreach ($lines as $line) $logs[] = array("timestamp" => substr($line, 0, 19), "log" => substr($line, 21));
		return $reverse ? array_reverse($logs) : $logs;
	}

	// private methods

	/**
	 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
	 * @author Torleif Berger, Lorenzo Stanco
	 * @link http://stackoverflow.com/a/15025877/995958
	 * @license http://creativecommons.org/licenses/by/3.0/
	 */
	private function tail($filepath, $lines = 1, $adaptive = true) {
		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;
		// Sets buffer size, according to the number of lines to retrieve.
		// This gives a performance boost when reading a few lines from the file.
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
		// Jump to last character
		fseek($f, -1, SEEK_END);
		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';
		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);
			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);
			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;
			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}
		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}
		// Close file and return
		fclose($f);
		return trim($output);
	}

}
