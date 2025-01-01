<?php declare(strict_types=1);

namespace Xrm;

use Xrm\Api\IXrm;

abstract class AbstractXrm implements IXrm {

	protected $servicelocator;
	protected $classmap;
	protected $session;
	protected $usermanager;
	protected $logger;

	protected $logging = false;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
		$this->session = $this->servicelocator->get('session');
		$this->usermanager = $this->servicelocator->get('usermanager');
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IXrm

	public function getFilteredEntries($filter) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getFilteredEntries", "filter" => $filter)));
		$entries = $this->getEntriesIntern($filter);
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getFilteredEntries", "num" => sizeof($entries))));
		return $entries;
	}

	public function setArchive($id, $archive) {
		$entry = $this->getEntry($id);
		$entry->archive = $archive ? 1 : 0;
		return !!$this->setEntry($entry);
	}

	public function connect($id1, $id2) {
		$res1 = $this->addAlloc($id1, $id2);
		$res2 = $this->addAlloc($id2, $id1);
		return $res1 && $res2;
	}

	public function disconnect($id1, $id2) {
		$res1 = $this->removeAlloc($id1, $id2);
		$res2 = $this->removeAlloc($id2, $id1);
		return $res1 && $res2;
	}

	/*
	 * Funktion arbeitet rekursiv bei and/or
	 * $filter kann nur null oder object sein
	 * Beispiele für $filter:
	 * { "attr": "ids", "op": "in", "val": [ "23fe", "c254", "6b87" ] }
	 * { "attr": "xrm", "op": "eq", "val": "Input" }
	 * { "attr": "type", "op": "eq", "val": "folder" }
	 * { "attr": "app", "op": "eq", "val": "Xrm" }
	 * { "attr": "alloc", "op": "with", "val": "3475a60e2c" }
	 * { "attr": "conj", "op": "or", "val": [ { "attr": "type", "op": "eq", "val": "link" }, { "attr": "type", "op": "eq", "val": "note" } ] }
	 * { "attr": "conj", "op": "and", "val": [ { "attr": "type", "op": "eq", "val": "link" }, { "attr": "alloc", "op": "eq", "val": "3a49c5e63f45" } ] }
	 */
	public function getEntriesIntern($filter, $idsonly = false) {

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntriesIntern", "filter" => $filter, "idsonly" => $idsonly)));
		if (is_array($filter)) $filter = (object) $filter;
		if (is_string($filter)) {
			$f = $filter;
			$filter = new \Xrm\XrmFilter;
			$filter->fromJson($f);
		}
		$entries = array();

		$bestprio = 0;
		$filtermodule = null;
		$instances = $this->classmap->getInstancesByInterface("Xrm\\Api\\IXrmFilterModule");
		foreach ($instances as $instance) {
			$prio = $instance->match($this, $filter);
			if (!$prio) continue;
			if ($prio > $bestprio) {
				$bestprio = $prio;
				$filtermodule = $instance;
			}
		}
		if ($filtermodule != null) $entries = $filtermodule->getEntries($this, $filter, $idsonly);

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'], "fn" => "getEntriesIntern", "num" => sizeof($entries))));
		return $entries;
	}

	// @return none | read | write
	public function getAccess($entry) {
		$entry = (object) $entry;
		if (substr($entry->id, 0, 2) == "xx") return "write";

		$session = $this->session && $this->session->started();
		$user = (object) ($session && isset($_SESSION["authentication"]) && isset($_SESSION["authentication"]["user"])
			? $_SESSION["authentication"]["user"]
			: $this->usermanager->getUser());
		$groups = $session && isset($_SESSION["authentication"]) && isset($_SESSION["authentication"]["groups"])
			? $_SESSION["authentication"]["groups"]
			: $this->usermanager->getGroups();
		foreach ($groups as $id => $group) $groups[$id] = (object) $group;
		if ($session) {
			$_SESSION["authentication"]["user"] = $user;
			$_SESSION["authentication"]["groups"] = $groups;
		}

		$user = (array) $user;
		if ($user != null && isset($user["role"]) && $user["role"] == "admin") return "write";

		$accessmodes = array(0 => "none", 1 => "read", 2 => "write");
		$accessmode = 0;
		foreach ($entry->access as $access) {
			$access = (object) $access;
			if ($access->usergroup == "user" && ($access->id == "default" || ($user != null && isset($user["id"]) && $access->id == $user["id"]))) {
				if ($access->mode == "read" && $accessmode < 1) $accessmode = 1;
				if ($access->mode == "owner" || $access->mode == "write") $accessmode = 2;
			} else if ($access->usergroup == "group" && $access->id == "default") {
				if ($access->mode == "read" && $accessmode < 1) $accessmode = 1;
				if ($access->mode == "write") $accessmode = 2;
			} else if ($access->usergroup == "group") {
				$found = false;
				foreach ($groups as $g) if ($g->id == $access->id) { $found = true; break; }
				if ($found) {
					if ($access->mode == "read" && $accessmode < 1) $accessmode = 1;
					if ($access->mode == "write") $accessmode = 2;
				}
			}
		}
		return $accessmodes[$accessmode];
	}

	public function getUserEntryId() {
		return null;
	}

	// Protected and helper methods

	protected function uuid() {
		return md5(microtime(true));
	}

}
