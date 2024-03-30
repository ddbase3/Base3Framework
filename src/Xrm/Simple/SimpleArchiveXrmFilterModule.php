<?php

namespace Xrm\Simple;

use Xrm\Api\IXrmFilterModule;

class SimpleArchiveXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "simplearchivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" && get_class($xrm) == "Xrm\\Simple\\SimpleXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "archive" && ($filter->val == 0 || $filter->val == 1)) {

			$ids = array();

			$this->database->connect();

			$op = $filter->op == "eq" ? " = " : " != ";
			$limit = $this->getLimitString($filter->offset, $filter->limit);
			$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `entry` WHERE `archive`" . $op . intval($filter->val) . $limit;
			$sysentries = $this->database->multiQuery($sql);
			foreach ($sysentries as $sysentry) $ids[] = $sysentry["uuid"];

			$entries = $idsonly ? $ids : $xrm->getEntries($ids);
		}

		return $entries;
	}

	// Private methods

	private function getLimitString($offset, $limit) {
		if ($offset == null && $limit != null) return " LIMIT " . intval($limit);
		else if ($offset != null && $limit == null) return " LIMIT " . intval($offset) . ", 18446744073709551615";
		else if ($offset != null && $limit != null) return " LIMIT " . intval($offset) . ", " . intval($limit);
		else return "";
	}

}
