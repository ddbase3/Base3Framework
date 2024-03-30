<?php

namespace Xrm\Media;

use Api\ICheck;
use Xrm\AbstractXrm;

class MediaXrm extends AbstractXrm implements ICheck {

	private $xrmglobal;

	private $xrmname;

	public function __construct($xrmname) {
		parent::__construct();
		$this->xrmglobal = $this->servicelocator->get('xrmglobal');
		$this->xrmname = $xrmname;
	}

	// Implementation of IXrm

	public function getXrmName() {
		return $this->xrmname;
	}

	public function delEntry($id, $moveonly = false) {
		// TODO implement
		return false;
	}

	public function setEntry($entry) {
		// TODO implement
		return null;
	}

	public function getEntry($id) {

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "id" => $id)));

		$filename = $this->getFilename($id);
		$result = null;

		if (file_exists($filename)) {
			$content = file_get_contents($filename);
			$result = json_decode($content, true);
		} else {
			if (substr($id, 0, 2) == "xx") {
				$result = array("id" => $id, "name" => substr($id, 4), "alloc" => array());
				$this->writeToFile($filename, json_encode($result));
			} else {
				if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "num" => 0)));
				return null;
			}
		}

		$entry = new \Xrm\XrmEntry;
		if (isset($result["id"])) $entry->id = $result["id"];
		if (isset($result["type"])) $entry->type = $result["type"];
		if (isset($result["name"])) $entry->name = $result["name"];
		if (isset($result["data"])) $entry->data = $result["data"];
		if (isset($result["archive"])) $entry->archive = $result["archive"] ? 1 : 0;
		if (isset($result["alloc"])) $entry->alloc = $result["alloc"];

		$entry->access = array();
		if (isset($result["access"])) foreach ($result["access"] as $data) {
			$access = new \Xrm\XrmEntryAccess;
			$access->mode = $data["mode"];
			$access->usergroup = $data["usergroup"];
			$access->id = $data["id"];
			$entry->access[] = $access;
		}

		$entry->log = array();
		if (isset($result["log"])) foreach ($result["log"] as $data) {
			$log = new \Xrm\XrmEntryLog;
			$log->action = $data["action"];
			$log->user = $data["user"];
			$log->timestamp = $data["timestamp"];
			$entry->log[] = $log;
		}

		$entry->xrmnames[] = $this->xrmname;

		$access = $this->getAccess($entry);
		if ($access == "none") return false;

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntry", "num" => 1)));
		return $entry;

	}

	public function getEntries($ids) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "ids" => $ids)));
		$entries = array();
		foreach ($ids as $id) {
			if (substr($id, 0, 2) == "xx") continue;
			$entry = $this->getEntry($id);
			if ($entry != null) $entries[] = $entry;
		}
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getEntries", "num" => sizeof($entries))));
		return $entries;
	}

	public function getAllocIds($id) {
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllocIds", "id" => $id)));
		$allocs = array();
		$entry = $this->getEntry($id);
		if ($entry && $this->getAccess($entry) != "none" && $entry->alloc) $allocs = $entry->alloc;

		// probieren, alle Allocs zu holen (dabei wird Berechtigung geprüft)
		$filter = new \Xrm\XrmFilter("ids", "in", $allocs);
		$entries = $this->xrmglobal->getEntriesIntern($filter, true);

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllocIds", "num" => sizeof($allocs))));
		return $entries;
	}

	public function getAllEntryIds() {
		$user = (object) $this->usermanager->getUser();
		if ($user->role != "admin") return array();

		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllEntryIds")));
		$entries = $this->getXrmEntryIdsRecursive(DIR_USERFILES . "mnt" . DIRECTORY_SEPARATOR . "nextcloud");
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getAllEntryIds", "num" => sizeof($entries))));
		return $entries;
	}

	public function getXrmEntryIds($xrmname, $invert = false) {
		$user = (object) $this->usermanager->getUser();
		if ($user->role != "admin") return array();

		if ((!$invert && $xrmname != $this->xrmname) || ($invert && $xrmname == $this->xrmname)) return array();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getXrmEntryIds", "xrmname" => $xrmname)));
		$entries = $this->getAllEntryIds();
		if ($this->logging) $this->logger->log("xrm", json_encode(array("host" => $_SERVER['HTTP_HOST'] , "fn" => "getXrmEntryIds", "num" => sizeof($entries), "entries" => $entries)));
		return $entries;
	}

	public function addTag($id, $tag) {
		$entry = $this->getEntry($id);
		if ($entry == null) return true;  // kein Fehler, dieses XRM ist nur nicht zuständig

		// access check in connect
		// if ($this->getAccess($entry) != "write") return false;

		$idtag = "xxta" . strtolower($tag);
		return $this->connect($id, $idtag);
	}

	public function removeTag($id, $tag) {
		$entry = $this->getEntry($id);
		if ($entry == null) return true;  // kein Fehler, dieses XRM ist nur nicht zuständig

		// access check in disconnect
		// if ($this->getAccess($entry) != "write") return false;

		$idtag = "xxta" . strtolower($tag);
		return $this->disconnect($id, $idtag);
	}

	public function addApp($id, $app) {
		$entry = $this->getEntry($id);
		if ($entry == null) return true;  // kein Fehler, dieses XRM ist nur nicht zuständig

		// access check in connect
		// if ($this->getAccess($entry) != "write") return false;

		$idapp = "xxap" . strtolower($app);
		return $this->connect($id, $idapp);
	}

	public function removeApp($id, $app) {
		$entry = $this->getEntry($id);
		if ($entry == null) return true;  // kein Fehler, dieses XRM ist nur nicht zuständig

		// access check in disconnect
		// if ($this->getAccess($entry) != "write") return false;

		$idapp = "xxap" . strtolower($app);
		return $this->disconnect($id, $idapp);
	}

	public function addAlloc($id1, $id2) {
		// only check access for id1, id2 is possible not reachable from this xrm

		$entry = $this->getEntry($id1);
		if ($entry == null) return true;  // kein Fehler, dieses XRM ist nur nicht zuständig, ... oder kein Zugriff???
		if (in_array($id2, $entry->alloc)) return true;

		// TODO wirklich notwendig? In setEntry wird auch geprüft
		if ($this->getAccess($entry) != "write") return false;

		$entry->alloc[] = $id2;
		$entry->alloc = array_values($entry->alloc);
		$result = $this->setEntry($entry) != null;
		return $result;  // kann fehlschlagen, z.B. mangels Zugriffsrechte
	}

	public function removeAlloc($id1, $id2) {
		// only check access for id1, id2 is possible not reachable from this xrm

		$entry = $this->getEntry($id1);
		if ($entry == null) return true;  // kein Fehler, dieses XRM ist nur nicht zuständig
		if (!in_array($id2, $entry->alloc)) return true;

		// TODO wirklich notwendig? In setEntry wird auch geprüft
		if ($this->getAccess($entry) != "write") return false;

		$entry->alloc = array_diff($entry->alloc, array($id2));
		$entry->alloc = array_values($entry->alloc);
		$result = $this->setEntry($entry) != null;
		return $result;  // kann fehlschlagen, z.B. mangels Zugriffsrechte
	}

	// Private methods

	private function getFilename($id) {
		$filename = $id . DIRECTORY_SEPARATOR . "xrmentry.json";
		if (substr($filename, 0, 2) == "x-") $filename = "x" . DIRECTORY_SEPARATOR . $filename;
			else $filename = substr(str_pad($filename, 2, "x"), 0, 2) . DIRECTORY_SEPARATOR . substr(str_pad($filename, 4, "x"), 2, 2) . DIRECTORY_SEPARATOR . $filename;
		return DIR_USERFILES . "mnt" . DIRECTORY_SEPARATOR . "nextcloud" . DIRECTORY_SEPARATOR . $filename;
	}

	private function writeToFile($filename, $content) {
		$dir = substr($filename, 0, strrpos($filename, DIRECTORY_SEPARATOR));
		if (!is_dir($dir)) mkdir($dir, 0777, true);
		$fp = fopen($filename, "w");
		fwrite($fp, $content);
		fclose($fp);
	}

	private function getXrmEntryIdsRecursive($dir) {
		$entries = array();
		$dh = opendir($dir);
		while ($file = readdir($dh)) {
			if ($file == "." || $file == ".." || $file == "xx" || $file == "lost+found") continue;
			$path = $dir . "/" . $file;
			if (is_dir($path) && !file_exists($path . "/xrmentry.json")) $entries = array_merge($entries, $this->getXrmEntryIdsRecursive($path));
				else if (is_dir($path)) $entries[] = $file;
		}
		closedir($dh);
		return $entries;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array();
	}

}
