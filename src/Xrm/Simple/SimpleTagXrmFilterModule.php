<?php

namespace Xrm\Simple;

use Xrm\Api\IXrmFilterModule;

class SimpleTagXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "simpletagxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "tag" && get_class($xrm) == "Xrm\\Simple\\SimpleXrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "tag" && ($filter->op == "conn" || $filter->op == "notconn")) {

			$ids = array();

			$this->database->connect();

			$sql = "";
			$limit = $this->getLimitString($filter->offset, $filter->limit);
			if ($filter->op == "conn") {
				$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `tag` WHERE `tag` = '" . $this->database->escape($filter->val) . "'" . $limit;
			} else if ($filter->op == "notconn") {
				$sql = "SELECT LOWER(HEX(e.`id`)) AS `uuid`
					FROM `entry` e
					LEFT JOIN `tag` t ON e.`id` = t.`id` AND t.`tag` = '" . $this->database->escape($filter->val) . "'
					WHERE t.`id` IS NULL"
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
