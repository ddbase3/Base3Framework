<?php

namespace Xrm\Simple;

use Xrm\Api\IXrmFilterModule;

class SimpleNameXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "simplenamexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "name" && get_class($xrm) == "Xrm\\Simple\\SimpleXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "name") {

			$ids = array();

			$this->database->connect();

			$wherestr = "0";
			if ($filter->op == "startswith") {
				$wherestr = "`name` LIKE '" . $this->database->escape($filter->val) . "%'";
			} else if ($filter->op == "endswith") {
				$wherestr = "`name` LIKE '%" . $this->database->escape($filter->val) . "'";
			} else if ($filter->op == "contains") {
				$wherestr = "`name` LIKE '%" . $this->database->escape($filter->val) . "%'";
			} else if ($filter->op == "notcontains") {
				$wherestr = "`name` NOT LIKE '%" . $this->database->escape($filter->val) . "%'";
			}

			$limit = $this->getLimitString($filter->offset, $filter->limit);

			$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `entry` WHERE " . $wherestr . $limit;

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
