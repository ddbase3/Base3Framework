<?php

namespace Desktop\Base;

use Desktop\Api\IDesktop;
use Api\ICheck;

class BaseDesktop implements IDesktop, ICheck {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "basedesktop";
	}

	// Implementation of IDesktop

	public function addTask($tasktype, $taskdata, $users) {
		$this->database->connect();
		$uuid = $this->uuid();
		$sql = "INSERT INTO `task` (`id`, `type`, `data`, `created`)
			VALUES (0x" . $uuid . ", '" . $this->database->escape($tasktype) . "', '" . $this->database->escape(json_encode($taskdata)) . "', NOW())";
		$this->database->nonQuery($sql);
		if ($users && sizeof($users)) {
			$sql = "INSERT INTO `taskuser` (`taskid`, `userid`) VALUES ";
			foreach ($users as $user) $sql .= "(0x" . $uuid . ", '" . $this->database->escape($user) . "'), ";
			$sql = substr($sql, 0, -2);
			$this->database->nonQuery($sql);
		}
		return $uuid;
	}

	public function numTasks($tasktype) {
		$this->database->connect();
		$sql = "SELECT COUNT(`id`) FROM `task` WHERE `type` = '" . $this->database->escape($tasktype) . "'";
		$num = $this->database->scalarQuery($sql);
		return intval($num);
	}

	public function waitingTasks($tasktype) {
		$this->database->connect();
		$sql = "SELECT COUNT(`id`) FROM `task` WHERE `type` = '" . $this->database->escape($tasktype) . "' AND `finished` LIKE '0000%'";
		$num = $this->database->scalarQuery($sql);
		return intval($num);
	}

	public function removeTask($taskid) {
		// User werden automatisch gelöscht wegen Foreign Key - Cascade
		$this->database->connect();
		$sql = "DELETE FROM `task` WHERE `id` = 0x" . $this->database->escape($taskid);
		$this->database->nonQuery($sql);
	}


	public function removeTasks($tasktype) {
		// User werden automatisch gelöscht wegen Foreign Key - Cascade
		$this->database->connect();
		$sql = "DELETE FROM `task` WHERE `type` = '" . $this->database->escape($tasktype) . "'";
		$this->database->nonQuery($sql);
	}


	public function getResults($tasktype) {
		$results = array();
		$this->database->connect();
		$sql = "SELECT LOWER(HEX(`id`)) AS `id`, `result` FROM `task` WHERE `type` = '" . $this->database->escape($tasktype) . "' AND `finished` > '1' AND `finished` < DATE_ADD(NOW(), INTERVAL -1 MINUTE)";
		$res = $this->database->multiQuery($sql);
		foreach ($res as $row) $results[$row["id"]] = json_decode($row["result"]);
		return $results;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->database == null ? "Fail" : "Ok"
		);
	}

	// Private methods

	private function uuid() {
		return md5(microtime(true));
	}

}
