<?php declare(strict_types=1);

namespace Base3\Configuration\ConfigFile;

use Base3\Api\ICheck;
use Base3\Configuration\Api\IConfiguration;

/**
 * Reads and optionally writes configuration data from a .ini file.
 * 
 * Uses lazy loading – config is read from file only on first access.
 */
class ConfigFile implements IConfiguration, ICheck {

	private string $filename;
	private ?array $cnf = null;

	public function __construct() {
		$this->filename = DIR_CNF . "config.ini";
		if ($env = getenv("CONFIG_FILE")) $this->filename = $env;
	}

	// Implementation of IConfiguration

	public function get($configuration = "") {
		$this->ensureLoaded();
		if (!strlen($configuration)) return $this->cnf;
		return $this->cnf[$configuration] ?? null;
	}

	public function set($data, $configuration = "") {
		$this->ensureLoaded();
		if (strlen($configuration)) {
			$this->cnf[$configuration] = $data;
		} else {
			$this->cnf = $data;
		}
	}

	public function save(): bool {
		$this->ensureLoaded();
		return $this->write_ini_file($this->filename, $this->cnf);
	}

	// Implementation of ICheck

	public function checkDependencies(): array {
		$this->ensureLoaded();
		return [
			'config_file_exists' => file_exists($this->filename) ? 'Ok' : 'config file not found',
			'data_directory_defined' => isset($this->cnf['directories']['data']) ? 'Ok' : 'data directory not defined'
		];
	}

	// Lazy loader

	private function ensureLoaded(): void {
		if ($this->cnf !== null) return;
		$this->cnf = [];
		$this->read_ini_file($this->filename);
	}

	// Private methods

	private function read_ini_file(string $file): void {
		if (!file_exists($file)) return;
		$cnf = parse_ini_file($file, true);
		$this->cnf = array_merge($this->cnf, $cnf);

		if (!isset($this->cnf["include"]["files"])) return;

		$datadir = $this->cnf['directories']['data'] ?? '';
		$files = $this->cnf["include"]["files"];
		unset($this->cnf["include"]);

		foreach ($files as $f) {
			$subfile = $datadir
				? $datadir . DIRECTORY_SEPARATOR . $f
				: dirname($file) . DIRECTORY_SEPARATOR . $f;

			$this->read_ini_file($subfile);
		}
	}

	private function write_ini_file(string $file, array $array): bool {
		$data = [];

		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$data[] = "[$key]";
				foreach ($val as $skey => $sval) {
					if (is_array($sval)) {
						foreach ($sval as $_skey => $_sval) {
							$keystr = is_numeric($_skey) ? $skey . '[]' : $skey . '[' . $_skey . ']';
							$data[] = $keystr . ' = ' . $this->iniEscape($_sval);
						}
					} else {
						$data[] = "$skey = " . $this->iniEscape($sval);
					}
				}
			} else {
				$data[] = "$key = " . $this->iniEscape($val);
			}
			$data[] = null; // empty line
		}

		$fp = fopen($file, 'w');
		if (!$fp) return false;

		$retries = 0;
		while (!flock($fp, LOCK_EX) && $retries++ < 100) usleep(rand(1, 5000));
		if ($retries >= 100) return false;

		fwrite($fp, implode(PHP_EOL, $data) . PHP_EOL);
		flock($fp, LOCK_UN);
		fclose($fp);

		return true;
	}

	private function iniEscape($value): string {
		if (is_numeric($value)) return (string)$value;
		if (ctype_upper((string)$value)) return (string)$value;
		return '"' . str_replace('"', '\"', (string)$value) . '"';
	}
}

