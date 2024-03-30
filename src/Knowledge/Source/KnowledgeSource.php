<?php

namespace Knowledge\Source;

use Knowledge\Api\IKnowledge;

class KnowledgeSource implements IKnowledge {

	private $scopes = array(
		"continent" => array(
			"id",
			"name_de",
			"name_en"
		),
		"country" => array(
			"id",
			"name_native",
			"name_en",
			"continent_id",
			"language_ids",
			"phone",
			"capital_en",
			"currency"
		),
		"language" => array(
			"id",
			"name_native",
			"name_en",
			"rtl",
			"country_ids"
		)
	);

	// Implementation of IKnowledge

	public function getScopes() {
		$result = array();
		foreach ($this->scopes as $scope => $_) $result[] = $scope;
		return $result;
	}

	public function getFields($scope) {
		return isset($this->scopes[$scope]) ? $this->scopes[$scope] : null;
	}

	public function getData($scope, $fields = null) {
		if ($fields == null && isset($this->scopes[$scope])) {
			$result = array();
			$data = $this->readFile($scope . "_en.json");
			foreach ($data as $k => $_) $result[] = $k;
			return $result;
		}
		if (!is_array($fields)) return null;
		switch ($scope) {
			case "continent": return $this->getDataContinent($fields);
			case "country": return $this->getDataCountry($fields);
			case "language": return $this->getDataLanguage($fields);
		}
		return null;
	}

	// private functions

	private function readFile($filename) {
		$json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "Data" . DIRECTORY_SEPARATOR . $filename);
		return json_decode($json, true);
	}

	private function getDataContinent($fields) {
		$result = array();
		$data = $this->readFile("continent_en.json");
		$data_de = in_array("name_de", $fields) ? $this->readFile("continent_de.json") : null;
		foreach ($data as $k => $v) {
			$line = array();
			if (in_array("id", $fields)) $line["id"] = $k;
			if (in_array("name_de", $fields)) $line["name_de"] = $data_de[$k];
			if (in_array("name_en", $fields)) $line["name_en"] = $v;
			$result[$k] = $line;
		}
		return $result;
	}

	private function getDataCountry($fields) {
		$result = array();
		$data = $this->readFile("country_en.json");
		foreach ($data as $k => $v) {
			$line = array();
			if (in_array("id", $fields)) $line["id"] = $k;
			if (in_array("name_native", $fields)) $line["name_native"] = $v["native"];
			if (in_array("name_en", $fields)) $line["name_en"] = $v["name"];
			if (in_array("continent_id", $fields)) $line["continent_id"] = $v["continent"];
			if (in_array("language_ids", $fields)) $line["language_ids"] = $v["languages"];
			if (in_array("phone", $fields)) $line["phone"] = $v["phone"];
			if (in_array("capital_en", $fields)) $line["capital_en"] = $v["capital"];
			if (in_array("currency", $fields)) $line["currency"] = $v["currency"];
			$result[$k] = $line;
		}
		return $result;
	}

	private function getDataLanguage($fields) {
		$result = array();
		$data = $this->readFile("language_en.json");
		$data_country = in_array("country_ids", $fields) ? $this->readFile("country_en.json") : null;
		foreach ($data as $k => $v) {
			$line = array();
			if (in_array("id", $fields)) $line["id"] = $k;
			if (in_array("name_native", $fields)) $line["name_native"] = $v["native"];
			if (in_array("name_en", $fields)) $line["name_en"] = $v["name"];
			if (in_array("rtl", $fields)) $line["rtl"] = isset($v["rtl"]) && $v["rtl"] ? 1 : 0;
			if (in_array("country_ids", $fields)) {
				$line["country_ids"] = array();
				foreach ($data_country as $ck => $cv)
					if (in_array($k, $cv["languages"]))
						$line["country_ids"][] = $ck;
			}
			$result[$k] = $line;
		}
		return $result;
	}

}
