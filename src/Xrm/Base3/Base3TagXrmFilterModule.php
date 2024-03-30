<?php

namespace Xrm\Base3;

use Xrm\Api\IXrmFilterModule;

class Base3TagXrmFilterModule implements IXrmFilterModule {

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
		return "base3tagxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "tag" && get_class($xrm) == "Xrm\\Base3\\Base3Xrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {

		$entries = array();

		if ($filter->attr == "tag" && ($filter->op == "conn" || $filter->op == "notconn")) {

			$ids = array();

			$this->database->connect();

			$sql = "";
			$limit = $this->getLimitString($filter->offset, $filter->limit);
			if ($filter->op == "conn") {
				$sql = "SELECT LOWER(HEX(e.`uuid`)) AS `uuid`
					FROM `base3system_sysentry` e
					INNER JOIN `base3system_systag` t ON e.`id` = t.`entry_id`
					WHERE e.`type_id` != 1 AND t.`tag` = '" . $this->database->escape($filter->val) . "'"
					. $limit;
			} else if ($filter->op == "notconn") {
				$sql = "SELECT LOWER(HEX(e.`uuid`)) AS `uuid`
					FROM `base3system_sysentry` e
					LEFT JOIN `base3system_systag` t ON e.`id` = t.`entry_id` AND `tag` = '" . $this->database->escape($filter->val) . "'
					WHERE e.`type_id` != 1 AND t.`entry_id` IS NULL"
					. $limit;
			}
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
