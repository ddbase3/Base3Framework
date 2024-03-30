<?php

namespace Xrm\Simple;

use Xrm\Api\IXrmFilterModule;

class SimpleLogXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "simplelogxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, array("owner", "created", "changed"))
			&& get_class($xrm) == "Xrm\\Simple\\SimpleXrm" ? 2 : 0;
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
				if ($filter->op == "eq") $wherestr = "`user` = '" . $this->database->escape($filter->val) . "'";
				if ($filter->op == "ne") $wherestr = "`user` != '" . $this->database->escape($filter->val) . "'";

				$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `log` WHERE `action` = 'created' AND " . $wherestr . $limit;

			} else {

				$wherestr = "0";
				if ($filter->op == "eq") $wherestr = "`timestamp` LIKE '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "ne") $wherestr = "`timestamp` NOT LIKE '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "gt") $wherestr = "`timestamp` > '" . $this->database->escape($filter->val) . " 23:59:59%'";
				if ($filter->op == "ge") $wherestr = "`timestamp` > '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "lt") $wherestr = "`timestamp` < '" . $this->database->escape($filter->val) . "%'";
				if ($filter->op == "le") $wherestr = "`timestamp` <= '" . $this->database->escape($filter->val) . " 23:59:59%'";

				if ($filter->attr == "created") {

					$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `log` WHERE `action` = 'created' AND " . $wherestr . $limit;

				} else if ($filter->attr == "changed" AND in_array($filter->op, array("gt", "ge"))) {

					$sql = "SELECT DISTINCT LOWER(HEX(`id`)) AS `uuid` FROM `log` WHERE `action` != 'read' AND " . $wherestr . $limit;

				} else if ($filter->attr == "changed") {

					$sql = "SELECT DISTINCT LOWER(HEX(l.`id`)) AS `uuid`
						FROM `log` l
						LEFT JOIN `log` l2 ON l2.`action` != 'read' AND l.`id` = l2.`id` AND l2.`timestamp` > l.`timestamp`
						WHERE l2.`id` IS NULL AND l.`action` != 'read' AND " . $wherestr
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
