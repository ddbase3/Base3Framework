<?php

namespace Scriptlock\Base;

use Scriptlock\Api\IScriptlock;

class ScriptlockBase implements IScriptlock {

	private $cnf = array();

	public function __construct($cnf = null) {

		if ($cnf == null) {
			$servicelocator = \Base3\ServiceLocator::getInstance();
			$configuration = $servicelocator->get('configuration');
			if ($configuration != null) $cnf = $configuration->get('scriptlock');
		}

		if ($cnf != null) $this->cnf = $cnf;
		if ($this->check()) $this->lock();
	}

	// Implementation of IScriptlock

	public function check() {
		if ($this->cnf == null) return false;
		if ($this->checkRemoteAddr()) return true;
		if ($this->checkAcceptLanguage()) return true;
		return false;
	}

	public function lock() {
		exit;
	}

	// private methods

	private function checkRemoteAddr() {
		if (!isset($_SERVER["REMOTE_ADDR"]) || !isset($this->cnf["declineremoteaddr"]) || !is_array($this->cnf["declineremoteaddr"])) return false;
		foreach ($this->cnf["declineremoteaddr"] as $ip) {
			$len = strlen($ip);
			if (substr($_SERVER["REMOTE_ADDR"], 0, $len) == $ip) return true;
		}
		return false;
	}

	private function checkAcceptLanguage() {
		if (!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) return false;
		if (!isset($this->cnf["declineacceptlanguage"]) || !is_array($this->cnf["declineacceptlanguage"])) return false;
		$langsstr = str_replace(";", ",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		$languages = explode(",", $langsstr);
		$remotelangs = array();
		foreach ($languages as $l) {
			if (strpos($l, "=") !== false) continue;
			if (strpos($l, "-") !== false) {
				$remotelangs[] = substr($l, 0, strpos($l, "-"));
				continue;
			}
			if (strpos($l, "-") !== false) {
				$remotelangs[] = $l;
				continue;
			}
		}
		foreach ($remotelangs as $l)
			if (in_array($l, $this->cnf["declineacceptlanguage"])) return true;
		return false;
	}

}
