<?php

namespace Status\File;

use Status\Api\IStatusHandler;
use Api\ICheck;

class FileStatusHandler implements IStatusHandler, ICheck {

	private $servicelocator;
	private $accesscontrol;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->accesscontrol = $this->servicelocator->get('accesscontrol');
	}

	private function getStatusFile() {
		$userid = $this->accesscontrol->getUserId();
		if ($userid == null) return null;
		$filename = DIR_LOCAL . "FileStatus" . DIRECTORY_SEPARATOR . $userid . ".json";
		return $filename;
	}

	private function loadStatus() {
		$filename = $this->getStatusFile();
		if ($filename == null) return null;
		if (!file_exists($filename)) return array();
		$content = file_get_contents($filename);
		$data = json_decode($content, true);
		return $data;
	}

	// Implementation of IStatusHandler

	public function get($fields = null) {
		$data = $this->loadStatus();
		if ($data == null) return null;
		if ($fields == null) return $data;
		if (!is_array($fields)) return isset($data[$fields]) ? $data[$fields] : null;
		$result = array();
		foreach ($fields as $field) if (isset($data[$field])) $result[$field] = $data[$field];
		return $result;
	}

	public function set($data) {
		$d = $this->loadStatus();
		if ($d == null) $d = array();
		foreach ($data as $k => $v) $d[$k] = $v;
		$fp = fopen($this->getStatusFile(), "w");
		fwrite($fp, json_encode($d));
		fclose($fp);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->accesscontrol == null ? "Fail" : "Ok"
		);
	}

}
