<?php

namespace Knowledge;

use Knowledge\Api\IKnowledge;
use Microservice\AbstractMicroservice;

class DevStatusMicroservice extends AbstractMicroservice implements IKnowledge {

	private $data;

	public function __construct() {
		$file = DIR_LOCAL . "devstatus.json";
		if (file_exists($file)) {
			$content = file_get_contents($file);
			$this->data = json_decode($content, 1);
		} else {
			$this->data = array();
		}
	}

	// Implementation of IKnowledge

	public function getScopes() {
		return array_keys($this->data);
	}

	public function getFields($scope) {
		if (!isset($this->data[$scope]) || !isset($this->data[$scope][0])) return null;
		return array_keys($this->data[$scope][0]);
	}

	public function getData($scope, $fields = null) {
		if (!isset($this->data[$scope])) return null;
		if ($fields == null) return $this->data[$scope];
		$result = array();
		foreach ($this->data[$scope] as $data) {
			$d = array();
			foreach ($fields as $field) $d[$field] = $data[$field];
			$result[] = $d;
		}
		return $result;
	}

}
