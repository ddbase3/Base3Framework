<?php declare(strict_types=1);

namespace Configuration\ConfigFile;

use Api\ICheck;
use Configuration\Api\IConfiguration;

class ConfigFile implements IConfiguration, ICheck {

	private $filename;
	private $cnf;

	public function __construct($filename = "") {

		$this->filename = DIR_CNF . "config.ini";
		if (!empty($filename)) $this->filename = $filename;
		if ($cnf = getenv("CONFIG_FILE")) $this->filename = $cnf;

		$this->read_ini_file($this->filename);
	}

	// Implementation of IConfiguration

	public function get($configuration = "") {
		if (!strlen($configuration)) return $this->cnf;
		return isset($this->cnf[$configuration]) ? $this->cnf[$configuration] : null;
	}

	public function set($data, $configuration = "") {
		if (strlen($configuration)) $this->cnf[$configuration] = $data;
			else $this->cnf = $data;
	}

	public function save() {
		return $this->write_ini_file($this->filename, $this->cnf);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"config_file_exists" => file_exists($this->filename) ? "Ok" : "config file not found"
		);
	}

	// Private methods

	private function read_ini_file($file) {
		if (!file_exists($file)) return;
		if (!$this->cnf) $this->cnf = array();
		$cnf = parse_ini_file($file, true);
		$this->cnf = array_merge($this->cnf, $cnf);

		if (!isset($this->cnf["include"]) || !isset($this->cnf["include"]["files"])) return;
		$files = $this->cnf["include"]["files"];
		unset($this->cnf["include"]);
		foreach ($files as $f) {
			$subfile = dirname($file) . "/" . $f;
			$this->read_ini_file($subfile);
		}
	}

	private function write_ini_file($file, $array = []) {
		// check first argument is string
		if (!is_string($file)) throw new \InvalidArgumentException('Function argument 1 must be a string.');

		// check second argument is array
		if (!is_array($array)) throw new \InvalidArgumentException('Function argument 2 must be an array.');

		// process array
		$data = array();
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$data[] = "[$key]";
				foreach ($val as $skey => $sval) {
					if (is_array($sval)) {
						foreach ($sval as $_skey => $_sval) {
							if (is_numeric($_skey)) {
								$data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
							} else {
								$data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
							}
						}
					} else {
						$data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
					}
				}
			} else {
				$data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
			}
			// empty line
			$data[] = null;
		}

		// open file pointer, init flock options
		$fp = fopen($file, 'w');
		$retries = 0;
		$max_retries = 100;

		if (!$fp) return false;

		// loop until get lock, or reach max retries
		do {
			if ($retries > 0) usleep(rand(1, 5000));
			$retries += 1;
		} while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

		// couldn't get the lock
		if ($retries == $max_retries) return false;

		// got lock, write data
		fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

		// release lock
		flock($fp, LOCK_UN);
		fclose($fp);

		return true;
	}

}
