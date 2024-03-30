<?php

namespace Xrm\Cache;

use Xrm\Api\IXrmFilterModule;

class CacheArchiveXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;
	private $logger;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IBase

	public function getName() {
		return "cachearchivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" && get_class($xrm) == "Xrm\\Cache\\CacheXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		if (!$idsonly) return array();

		$filter = (object) $filter;

		$entries = array();

		if ($filter->attr == "archive") {

			$this->database->connect();

			$wherestr = "0";
			if ($filter->op == "eq" && $filter->val != "undef") {
				$wherestr = "`archive` = " . intval($filter->val);
			} else if ($filter->op == "eq" && $filter->val == "undef") {
				$wherestr = "0";
			} else if ($filter->op == "neq" && $filter->val == "undef") {
				$wherestr = "1";
			} else if ($filter->op == "neq" && $filter->val != "undef") {
				$wherestr = "`archive` != " . intval($filter->val);
			}

			$limit = $this->getLimitString($filter->offset, $filter->limit);

			$sql = "SELECT DISTINCT LOWER(HEX(`uuid`)) AS `uuid` FROM `xrmentry` WHERE " . $wherestr . $limit;

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
