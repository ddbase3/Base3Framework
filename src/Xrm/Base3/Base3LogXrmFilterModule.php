<?php

namespace Xrm\Base3;

use Xrm\Api\IXrmFilterModule;

class Base3LogXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "base3logxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, array("owner", "created", "changed"))
			&& get_class($xrm) == "Xrm\\Base3\\Base3Xrm" ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if (in_array($filter->attr, array("owner", "created", "changed"))) {

			$ids = array();

			$this->database->connect();

			$sql = "";
			$limit = $this->getLimitString($filter->offset, $filter->limit);

			if ($filter->attr == "owner") {

				$wherestr = "0";
				if ($filter->op == "eq") $wherestr = "u.`name` = '" . $this->database->escape($filter->val) . "'";
				if ($filter->op == "ne") $wherestr = "u.`name` != '" . $this->database->escape($filter->val) . "'";

				$sql = "SELECT LOWER(HEX(e.`uuid`)) AS `uuid`
					FROM `base3system_syslog` l
					INNER JOIN `base3system_sysuser` u ON l.`user_id` = u.`id`
					INNER JOIN `base3system_sysentry` e ON l.`entry_id` = e.`id`
					WHERE e.`type_id` != 1 AND l.`action` = 'new' AND " . $wherestr
					. $limit;

			} else {

				$wherestr = "0";
				if ($filter->op == "eq") $wherestr = "l.`datetime` LIKE '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "ne") $wherestr = "l.`datetime` NOT LIKE '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "gt") $wherestr = "l.`datetime` > '" . $this->database->escape($filter->val) . " 23:59:59%'";
				if ($filter->op == "ge") $wherestr = "l.`datetime` > '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "lt") $wherestr = "l.`datetime` < '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "le") $wherestr = "l.`datetime` <= '" . $this->database->escape($filter->val) . " 23:59:59%'";

				if ($filter->attr == "created") {

					$sql = "SELECT LOWER(HEX(e.`uuid`)) AS `uuid`
						FROM `base3system_syslog` l
						INNER JOIN `base3system_sysentry` e ON l.`entry_id` = e.`id`
						WHERE e.`type_id` != 1 AND l.`action` = 'new' AND " . $wherestr
						. $limit;

				} else if ($filter->attr == "changed" AND in_array($filter->op, array("gt", "ge"))) {

					$sql = "SELECT DISTINCT LOWER(HEX(e.`uuid`)) AS `uuid`
						FROM `base3system_syslog` l
						INNER JOIN `base3system_sysentry` e ON l.`entry_id` = e.`id`
						WHERE e.`type_id` != 1 AND l.`action` != 'read' AND " . $wherestr
						. $limit;

				} else if ($filter->attr == "changed") {

					$sql = "SELECT DISTINCT LOWER(HEX(e.`uuid`)) AS `uuid`
						FROM `base3system_syslog` l
						LEFT JOIN `base3system_syslog` l2 ON l2.`action` != 'read' AND l.`entry_id` = l2.`entry_id` AND l2.`datetime` > l.`datetime`
						INNER JOIN `base3system_sysentry` e ON l.`entry_id` = e.`id`
						WHERE e.`type_id` != 1 AND l2.`id` IS NULL AND l.`action` != 'read' AND " . $wherestr
						. $limit;

				}

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
