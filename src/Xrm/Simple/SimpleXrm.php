<?php

namespace Xrm\Simple;

use Api\ICheck;
use Xrm\AbstractXrm;

class SimpleXrm extends AbstractXrm implements ICheck {

	private $database;
	private $xrmglobal;

	private $xrmname;

	public function __construct($xrmname) {
		parent::__construct();
		$this->database = $this->servicelocator->get('database');
		$this->xrmglobal = $this->servicelocator->get('xrmglobal');
		$this->xrmname = $xrmname;
	}

	// Implementation of IXrm

	public function getXrmName() {
		return $this->xrmname;
	}

	public function delEntry($id, $moveonly = false) {
		$entry = $this->getEntry($id);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		$sql = "SELECT `type` FROM `entry` WHERE `id` = 0x" . $this->database->escape($id);
		$sysentry = $this->database->singleQuery($sql);
		if ($sysentry == null) return false;

		$sql = "DELETE FROM `data_" . $sysentry["type"] . "` WHERE `id` = 0x" . $this->database->escape($id);
		$this->database->nonQuery($sql);

		if (!$moveonly) {
			$sql = "DELETE FROM `alloc` WHERE `id1` = 0x" . $this->database->escape($id) . " OR `id2` = 0x" . $this->database->escape($id);
			$this->database->nonQuery($sql);
		}

		$sql = "DELETE FROM `access` WHERE `id` = 0x" . $this->database->escape($id);
		$this->database->nonQuery($sql);

		$sql = "DELETE FROM `app` WHERE `id` = 0x" . $this->database->escape($id);
		$this->database->nonQuery($sql);

		$sql = "DELETE FROM `tag` WHERE `id` = 0x" . $this->database->escape($id);
		$this->database->nonQuery($sql);

		$sql = "DELETE FROM `log` WHERE `id` = 0x" . $this->database->escape($id);
		$this->database->nonQuery($sql);

		$sql = "DELETE FROM `entry` WHERE `id` = 0x" . $this->database->escape($id);
		$this->database->nonQuery($sql);

		return true;
	}

	public function setEntry($entry) {

		if (is_array($entry)) $entry = (object) $entry;
		if ($entry->id != null && substr($entry->id, 0, 2) == "xx") return false;

		$user = (object) $this->usermanager->getUser();
		$userid = $user ? $user->id : "";

		$newEntry = null;
		if ($entry->id == null) {
			$newEntry = new \Xrm\XrmEntry;
			$newEntry->id = $this->uuid();
		} else {
			$newEntry = $this->getEntry($entry->id);
			if ($newEntry) {
				if ($this->getAccess($newEntry) != "write") return null;
			} else {
				$newEntry = new \Xrm\XrmEntry;
				$newEntry->id = $entry->id;
			}
		}

		if ($entry->name != null) $newEntry->name = $entry->name;
		if ($entry->type != null) $newEntry->type = $entry->type;
		if ($entry->data != null) $newEntry->data = $entry->data;
		if ($entry->archive != null) $newEntry->archive = $entry->archive ? 1 : 0;

		// TODO alten Eintrag holen und allocs, tags, apps vergleichen. Bei Änderungen auch in den Verbundenen ändern (also ggfs. hinzufügen/löschen)!

		$removeAllocs = array_diff($newEntry->alloc == null ? array() : $newEntry->alloc, $entry->alloc == null ? array() : $entry->alloc);
		$addAllocs = array_diff($entry->alloc == null ? array() : $entry->alloc, $newEntry->alloc == null ? array() : $newEntry->alloc);
		if ($entry->alloc !== null) $newEntry->alloc = $entry->alloc;  // !== wichtig, da empty array == null
		if ($entry->xrmnames != null) $newEntry->xrmnames = $entry->xrmnames;

		$newEntry->access = $entry->access == null ? array() : $entry->access;
		if ($entry->id == null) {
			$access = new \Xrm\XrmEntryAccess;
			$access->mode = "owner";
			$access->usergroup = "user";
			$access->id = $userid;
			$newEntry->access[] = $access;
		}

		if (substr($entry->id, 0, 2) != "xx") {
			$newEntry->log = $entry->log == null ? array() : $entry->log;
			$log = new \Xrm\XrmEntryLog;
			$log->action = $entry->id == null ? "created" : "changed";
			$log->user = $userid;
			$log->timestamp = date("Y-m-d H:i:s");
			$newEntry->log[] = $log;
		}



		// save to db

		$this->database->connect();

		// TODO Update !!!

		$archive = $newEntry->archive ? 1 : 0;
		$sql = "INSERT INTO `entry` SET `id` = 0x" . $this->database->escape($newEntry->id) . ", `type` = '" . $this->database->escape($newEntry->type) . "',
				`name` = '" . $this->database->escape($newEntry->name) . "', `archive` = " . $archive;
		$this->database->nonQuery($sql);

		// corresponding data table
		// TODO ggfs überschüssige Daten in sysmetadata speichern (vorher Tabellen-Signatur holen)
		$sql = "INSERT INTO `data_" . $this->database->escape($newEntry->type) . "` SET `id` = 0x" . $this->database->escape($newEntry->id);
		foreach ($entry->data as $n => $v) $sql .= ", `" . $n . "` = '" . $this->database->escape($v) . "'";
		$this->database->nonQuery($sql);

		// sysalloc
		// TODO nur varbinary(16)-IDs können aktuell gespeichert werden ... PRÜFEN/LÖSEN !!!
		foreach ($removeAllocs as $alloc) {
			if (substr($alloc, 0, 2) == "xx") continue;
			$this->removeAlloc($alloc, $newEntry->id);
		}
		foreach ($addAllocs as $alloc) {
			if (substr($alloc, 0, 2) == "xx") continue;
			$sql = "INSERT INTO `alloc` (`id1`, `id2`) VALUES (0x" . $this->database->escape($newEntry->id) . ", 0x" . $this->database->escape($alloc) . ")";
			$this->database->nonQuery($sql);
		}

		// sysapp
		foreach ($removeAllocs as $alloc) {
			if (substr($alloc, 0, 4) != "xxap") continue;
			$app = strtoupper(substr($alloc, 4, 1)) . substr($alloc, 5);
			$sql = "DELETE FROM `app` WHERE `id` = 0x" . $this->database->escape($newEntry->id) . " AND `app` = '" . $this->database->escape($app) . "'";
			$this->database->nonQuery($sql);
		}
		foreach ($addAllocs as $alloc) {
			if (substr($alloc, 0, 4) != "xxap") continue;
			$app = strtoupper(substr($alloc, 4, 1)) . substr($alloc, 5);
			$sql = "INSERT INTO `app` SET `id` = 0x" . $this->database->escape($newEntry->id) . ", `app` = '" . $this->database->escape($app) . "'";
			$this->database->nonQuery($sql);
		}

		// systag
		foreach ($removeAllocs as $alloc) {
			if (substr($alloc, 0, 4) != "xxta") continue;
			$tag = substr($alloc, 4);
			$sql = "DELETE FROM `tag` WHERE `id` = 0x" . $this->database->escape($newEntry->id) . " AND `tag` = '" . $this->database->escape($tag) . "'";
			$this->database->nonQuery($sql);
		}
		foreach ($addAllocs as $alloc) {
			if (substr($alloc, 0, 4) != "xxta") continue;
			$tag = substr($alloc, 4);
			$sql = "INSERT INTO `tag` SET `id` = 0x" . $this->database->escape($newEntry->id) . ", `tag` = '" . $this->database->escape($tag) . "'";
			$this->database->nonQuery($sql);
		}

		// syslog
		foreach ($newEntry->log as $log) {
			$log = (object) $log;
			$sql = "INSERT INTO `log` (`id`, `action`, `user`, `timestamp`)
				VALUES (0x" . $this->database->escape($newEntry->id) . ", '" . $this->database->escape($log->action) . "',
					'" . $this->database->escape($log->user) . "', '" . $this->database->escape($log->timestamp) . "')";
			$this->database->nonQuery($sql);
		}

		// sysuseraccess, sysgroupaccess
		foreach ($newEntry->access as $access) {
			$access = (object) $access;
			$sql = "INSERT INTO `access` (`id`, `mode`, `usergroup`, `ugid`)
				VALUES (0x" . $this->database->escape($newEntry->id) . ", '" . $this->database->escape($access->mode) . "',
					'" . $this->database->escape($access->usergroup) . "', '" . $this->database->escape($access->id) . "')";
			$this->database->nonQuery($sql);
		}

		$newEntry->xrmnames[] = $this->xrmname;

		return $newEntry;
	}

	public function getEntry($id) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "id" => $id)));
		$entries = $this->getEntries(array($id));
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "num" => sizeof($entries) ? 1 : 0)));
		return sizeof($entries) ? array_pop($entries) : null;
	}

	public function getEntries($ids) {
		$blocksize = 100;
		$entries = array();
		$n = ceil(sizeof($ids) / $blocksize);
		for ($i = 0; $i < $n; $i++) {
			$subset = array_slice($ids, $i * $blocksize, $blocksize);
			$res = $this->getEntriesSub($subset);
			$entries = array_merge($entries, $res);
		}
		return $entries;
	}

	private function getEntriesSub(&$ids) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "ids" => $ids)));

		$entrytypes = array();
		$entryback = array();
		$entryids = array();
		$entries = array();

		$selids = array();
		foreach ($ids as $id) {
			if (substr($id, 0, 2) == "xx") continue;
			$selids[] = $id;
		}

		$this->database->connect();

		$sql = "SELECT LOWER(HEX(`id`)) AS `uuid`, `type`, `name`, `archive` FROM `entry` WHERE `id` IN (0x" . implode(", 0x", $selids) . ")";
		$sysentries = $this->database->multiQuery($sql);
		if (!sizeof($sysentries)) {
			if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "num" => 0)));
			return array();
		}

		$types = array();
		$entryids = array();
		foreach ($sysentries as $sysentry) {
			$entry = new \Xrm\XrmEntry;
			$entry->id = $sysentry["uuid"];
			$entry->type = $sysentry["type"];
			$entry->name = $sysentry["name"];
			$entry->archive = $sysentry["archive"] ? 1 : 0;
			$entry->alloc = array("xxty" . $sysentry["type"]);
			$entry->access = array();
			$entry->log = array();
			$entries[$sysentry["uuid"]] = $entry;

			$types[$sysentry["type"]][] = $sysentry["uuid"];
			$entryids[] = $sysentry["uuid"];
		}

		foreach ($types as $type => $typeids) {
			$sql = "SELECT *, LOWER(HEX(`id`)) AS `uuid` FROM `data_" . $type . "` WHERE `id` IN (0x" . implode(", 0x", $typeids) . ")";
			$datas = $this->database->multiQuery($sql);
			foreach ($datas as $data) {
				$uuid = $data["uuid"];
				$entries[$uuid]->data = $data;
				unset($entries[$uuid]->data["uuid"]);
				unset($entries[$uuid]->data["id"]);
			}
		}

		$sql = "SELECT LOWER(HEX(`id2`)) AS `uuid`, LOWER(HEX(`id1`)) AS `alloc` FROM `alloc` WHERE `id2` IN (0x" . implode(", 0x", $entryids) . ")
			UNION
			SELECT LOWER(HEX(`id1`)) AS `uuid`, LOWER(HEX(`id2`)) AS `alloc` FROM `alloc` WHERE `id1` IN (0x" . implode(", 0x", $entryids) . ")
			UNION
			SELECT LOWER(HEX(`id`)) AS `uuid`, CONCAT('xxta', LOWER(`tag`)) AS `alloc` FROM `tag` WHERE `id` IN (0x" . implode(", 0x", $entryids) . ")
			UNION
			SELECT LOWER(HEX(`id`)) AS `uuid`, CONCAT('xxap', LOWER(`app`)) AS `alloc` FROM `app` WHERE `id` IN (0x" . implode(", 0x", $entryids) . ")";
		$sysentries = $this->database->multiQuery($sql);
		foreach ($sysentries as $sysentry) $entries[$sysentry["uuid"]]->alloc[] = $sysentry["alloc"];

		$sql = "SELECT LOWER(HEX(`id`)) AS `uuid`, `mode`, `usergroup`, `ugid` FROM `access` WHERE `id` IN (0x" . implode(", 0x", $entryids) . ")";
		$sysentries = $this->database->multiQuery($sql);
		foreach ($sysentries as $sysentry) {
			$access = new \Xrm\XrmEntryAccess;
			$access->mode = $sysentry["mode"];
			$access->usergroup = $sysentry["usergroup"];
			$access->id = $sysentry["ugid"];
			$entries[$sysentry["uuid"]]->access[] = $access;
		}

		$sql = "SELECT LOWER(HEX(`id`)) AS `uuid`, `action`, `user`, `timestamp` FROM `log` WHERE `id` IN (0x" . implode(", 0x", $entryids) . ")";
		$sysentries = $this->database->multiQuery($sql);
		foreach ($sysentries as $sysentry) {
			$log = new \Xrm\XrmEntryLog;
			$log->action = $sysentry["action"];
			$log->user = $sysentry["user"];
			$log->timestamp = $sysentry["timestamp"];
			$entries[$sysentry["uuid"]]->log[] = $log;
		}

		foreach ($entries as $id => $entry) {
			$entries[$id]->xrmnames[] = $this->xrmname;
		}

		foreach ($entries as $id => $entry) {
			$access = $this->getAccess($entry);
			if ($access == "none") unset($entries[$id]);
		}

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "num" => sizeof($entries))));
		return $entries;
	}

	public function getAllocIds($id) {

		// no access check on allocs, because only ids
		// jetzt doch

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllocIds", "id" => $id)));

		$this->database->connect();

		$entries = array();

		if (substr($id, 0, 4) == "xxta") {

			$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `tag` WHERE LOWER(`tag`) = '" . substr($id, 4) . "'";

		} else if (substr($id, 0, 4) == "xxap") {

			$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `app` WHERE LOWER(`app`) = '" . substr($id, 4) . "'";

		} else if (substr($id, 0, 4) == "xxty") {

			$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `entry` WHERE `type` = '" . substr($id, 4) . "'";

		} else {

			$sql = "SELECT LOWER(HEX(`id2`)) AS `uuid` FROM `alloc` WHERE `id1` = 0x" . $id . "
				UNION
				SELECT LOWER(HEX(`id1`)) AS `uuid` FROM `alloc` WHERE `id2` = 0x" . $id . "
				UNION
				SELECT CONCAT('xxty', LOWER(`type`)) AS `uuid` FROM `entry` WHERE `id` = 0x" . $id . "
				UNION
				SELECT CONCAT('xxap', LOWER(`app`)) AS `uuid` FROM `app` WHERE `id` = 0x" . $id . "
				UNION
				SELECT CONCAT('xxta', LOWER(`tag`)) AS `uuid` FROM `tag` WHERE `id` = 0x" . $id;

		}

		$sysentries = $this->database->multiQuery($sql);
		foreach ($sysentries as $sysentry) $entries[] = $sysentry["uuid"];

/*
// ALT: geht so nicht, nicht für Einträge aus anderen XRMs
		$es = array();
		$all = $this->getAllEntryIds();
		foreach ($entries as $entry) if (substr($entry, 0, 2) == "xx" || in_array($entry, $all)) $es[] = $entry;
*/

		// probieren, alle Allocs zu holen (dabei wird Berechtigung geprüft)
		$filter = new \Xrm\XrmFilter("ids", "in", $entries);
		$es = $this->xrmglobal->getEntriesIntern($filter, true);

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllocIds", "num" => sizeof($entries), "entries" => $entries)));
		return $es;
	}

	public function getAllEntryIds() {

		// no access check on allocs, because only ids
		// jetzt doch

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllEntryIds")));

		$groups = array();
		$user = (object) $this->usermanager->getUser();
		if ($user->role != "admin") $groups = $this->usermanager->getGroups();

		$entries = array();

		$this->database->connect();

		$sql = "";
		if ($user->role == "admin") {
			$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `entry`";
		} else {
			$gs = array();
			foreach ($groups as $group) {
				$g = (object) $group;
				$gs[] = $this->database->escape($g->id);
			}

			$sql = "SELECT DISTINCT LOWER(HEX(e.`id`)) AS `uuid`
				FROM `entry` e
				LEFT JOIN `access` a1 ON e.`id` = a1.`id` AND a1.`usergroup` = 'user' AND a1.`ugid` = '" . $this->database->escape($user->id) . "'
				LEFT JOIN `access` a2 ON e.`id` = a2.`id` AND a2.`usergroup` = 'group' AND a2.`ugid` IN ('" . implode("', '", $gs) . "')
				WHERE a1.`id` IS NOT NULL OR a2.`id` IS NOT NULL";
		}
		$sysentries = $this->database->multiQuery($sql);
		foreach ($sysentries as $sysentry) $entries[] = $sysentry["uuid"];

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllEntryIds", "num" => sizeof($entries))));
		return $entries;
	}

	public function getXrmEntryIds($xrmname, $invert = false) {

		// no access check on allocs, because only ids
		// jetzt doch

		if ((!$invert && $xrmname != $this->xrmname) || ($invert && $xrmname == $this->xrmname)) return array();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getXrmEntryIds", "xrmname" => $xrmname)));
		$entries = $this->getAllEntryIds();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getXrmEntryIds", "num" => sizeof($entries))));
		return $entries;
	}

	public function addTag($id, $tag) {
		$entry = $this->getEntry($id);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		$sql = "INSERT INTO `tag` SET `id` = 0x" . $this->database->escape($id) . ", `tag` = '" . $this->database->escape($tag) . "'";
		$this->database->nonQuery($sql);

		return true;
	}

	public function removeTag($id, $tag) {
		$entry = $this->getEntry($id);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		$sql = "DELETE FROM `tag` WHERE `id` = 0x" . $this->database->escape($id) . " AND `tag` = '" . $this->database->escape($tag) . "'";
		$this->database->nonQuery($sql);

		return true;
	}

	public function addApp($id, $app) {
		$entry = $this->getEntry($id);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		$sql = "INSERT INTO `app` SET `id` = 0x" . $this->database->escape($id) . ", `app` = '" . $this->database->escape($app) . "'";
		$this->database->nonQuery($sql);

		return true;
	}

	public function removeApp($id, $app) {
		$entry = $this->getEntry($id);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		$sql = "DELETE FROM `app` WHERE `id` = 0x" . $this->database->escape($id) . " AND `app` = '" . $this->database->escape($app) . "'";
		$this->database->nonQuery($sql);

		return true;
	}

	public function addAlloc($id1, $id2) {
		// only check access for id1, id2 is possible not reachable from this xrm

		$entry = $this->getEntry($id1);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		// only create one alloc
		if ($id2 < $id1) {
			$tmp = $id1;
			$id1 = $id2;
			$id2 = $tmp;
		}

		$sql = "INSERT INTO `alloc` SET `id1` = 0x" . $this->database->escape($id1) . ", `id2` = 0x" . $this->database->escape($id2);
		$this->database->nonQuery($sql);

		return true;
	}

	public function removeAlloc($id1, $id2) {
		// only check access for id1, id2 is possible not reachable from this xrm

		$entry = $this->getEntry($id1);
		if (!$entry || $this->getAccess($entry) != "write") return false;

		$this->database->connect();

		$sql = "DELETE FROM `alloc`
				WHERE (`id1` = 0x" . $this->database->escape($id1) . " AND `id2` = 0x" . $this->database->escape($id2) . ")
				OR (`id2` = 0x" . $this->database->escape($id1) . " AND `id1` = 0x" . $this->database->escape($id2) . ")";
		$this->database->nonQuery($sql);

		return true;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->database == null ? "Fail" : "Ok"
		);
	}

}
