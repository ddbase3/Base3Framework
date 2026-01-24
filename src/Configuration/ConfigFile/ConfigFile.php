<?php declare(strict_types=1);

namespace Base3\Configuration\ConfigFile;

use Base3\Api\ICheck;
use Base3\Configuration\AbstractConfiguration;

/**
 * Class ConfigFile
 *
 * Reads and optionally writes configuration data from a .ini file.
 *
 * - Lazy loading (first access triggers load)
 * - Supports include files via [include] files[] entries
 * - Uses AbstractConfiguration for all convenience methods + dirty tracking
 */
class ConfigFile extends AbstractConfiguration implements ICheck {

	private string $filename;

	public function __construct() {
		$this->filename = DIR_CNF . "config.ini";
		if ($env = getenv("CONFIG_FILE")) $this->filename = $env;
	}

	// ---------------------------------------------------------------------
	// ICheck
	// ---------------------------------------------------------------------

	public function checkDependencies(): array {
		$this->ensureLoaded();
		return [
			'config_file_exists' => file_exists($this->filename) ? 'Ok' : 'config file not found',
			'data_directory_defined' => isset($this->cnf['directories']['data']) ? 'Ok' : 'data directory not defined'
		];
	}

	// ---------------------------------------------------------------------
	// AbstractConfiguration
	// ---------------------------------------------------------------------

	protected function load(): array {
		$cnf = [];

		if (file_exists($this->filename)) {
			$this->read_ini_file($this->filename, $cnf);
		}

		return $cnf;
	}

	protected function saveData(array $data): bool {
		return $this->write_ini_file($this->filename, $data);
	}

	// ---------------------------------------------------------------------
	// INI helpers (supports recursive includes)
	// ---------------------------------------------------------------------

	private function read_ini_file(string $file, array &$cnf): void {
		if (!file_exists($file)) return;

		$parsed = parse_ini_file($file, true);
		if (is_array($parsed)) {
			$cnf = array_replace_recursive($cnf, $parsed);
		}

		if (!isset($cnf["include"]["files"])) return;

		$datadir = $cnf['directories']['data'] ?? '';
		$files = $cnf["include"]["files"];
		unset($cnf["include"]);

		foreach ($files as $f) {
			$subfile = $datadir
				? $datadir . DIRECTORY_SEPARATOR . $f
				: dirname($file) . DIRECTORY_SEPARATOR . $f;

			$this->read_ini_file($subfile, $cnf);
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
