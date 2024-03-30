<?php

namespace Xrm\Cache;

use Xrm\Api\IXrmFilterModule;

class CacheLogXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "cachelogxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, array("owner", "created", "changed"))
			&& get_class($xrm) == "Xrm\\Cache\\CacheXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		if (!$idsonly) return array();

		$filter = (object) $filter;

		$entries = array();

		if (in_array($filter->attr, array("owner", "created", "changed"))) {

			$this->database->connect();

			$sql = "";

			$val = $this->database->escape($filter->val);
			$limit = $this->getLimitString($filter->offset, $filter->limit);

			if ($filter->attr == "owner") {

				$op = $filter->op == "eq" ? " = " : " != ";
				$sql = "SELECT DISTINCT LOWER(HEX(`uuid`)) AS `uuid` FROM `xrmentry` WHERE `owner` " . $op . " '" . $val . "'" . $limit;

			} else {

				$attr = $this->database->escape($filter->attr);
				if ($filter->op == "eq") $wherestr = "`" . $attr . "` LIKE '" . $val . "%'";
				if ($filter->op == "ne") $wherestr = "`" . $attr . "` NOT LIKE '" . $val . "%'";
				if ($filter->op == "gt") $wherestr = "`" . $attr . "` > '" . $val . " 23:59:59%'";
				if ($filter->op == "ge") $wherestr = "`" . $attr . "` > '" . $val . "%'";
				if ($filter->op == "lt") $wherestr = "`" . $attr . "` < '" . $val . "%'";
				if ($filter->op == "le") $wherestr = "`" . $attr . "` <= '" . $val . " 23:59:59%'";
				$sql = "SELECT DISTINCT LOWER(HEX(`uuid`)) AS `uuid` FROM `xrmentry` WHERE " . $wherestr . $limit;

			}

			$sysentries = $this->database->multiQuery($sql);
			foreach ($sysentries as $sysentry) $entries[] = $sysentry["uuid"];

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
