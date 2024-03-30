<?php

namespace Xrm;

class XrmEntry {

	public $id;
	public $name;
	public $type;
	public $data;
	public $archive;
	public $etag;
	public $created;
	public $changed;
	public $alloc;
	public $access;
	public $log;
	public $xrmnames;

	public function __construct() {
		$this->archive = 0;
		$this->alloc = array();
		$this->access = array();
		$this->log = array();
		$this->xrmnames = array();
	}

	public static function unserialize($data) {

		// wenn $data schon ein XrmEntry ist, dann direkt zurückgeben
		if (is_object($data) && is_a($data, 'Xrm\XrmEntry')) return $data;

		if (is_string($data)) $data = json_decode($data, 1);
		if (is_object($data)) $data = (array) $data;

		$xrmentry = new self();
		if (isset($data["id"])) $xrmentry->id = $data["id"];
		if (isset($data["name"])) $xrmentry->name = $data["name"];
		if (isset($data["type"])) $xrmentry->type = $data["type"];
		if (isset($data["data"])) $xrmentry->data = $data["data"];
		if (isset($data["archive"])) $xrmentry->archive = $data["archive"];
		if (isset($data["etag"])) $xrmentry->etag = $data["etag"];
		if (isset($data["created"])) $xrmentry->created = $data["created"];
		if (isset($data["changed"])) $xrmentry->changed = $data["changed"];
		if (isset($data["alloc"])) $xrmentry->alloc = $data["alloc"];

		if (isset($data["access"]))
			foreach ($data["access"] as $access)
				$xrmentry->access[] = \Xrm\XrmEntryAccess::unserialize($access);

		if (isset($data["log"]))
			foreach ($data["log"] as $log)
				$xrmentry->log[] = \Xrm\XrmEntryLog::unserialize($log);

		if (isset($data["xrmnames"])) $xrmentry->xrmnames = $data["xrmnames"];

		return $xrmentry;
	}

	/* alloc methods */

	public function isConnected($id) {
		foreach ($this->alloc as $alloc) {
			if (substr($alloc, 0, 2) == "xx") continue;
			if ($alloc == $id) return true;
		}
		return false;
	}

	/* tag methods */

	public function getTags() {
		$result = array();
		foreach ($this->alloc as $alloc) if (substr($alloc, 0, 4) == "xxta") $result[] = substr($alloc, 4);
		return $result;
	}

	public function hasTag($tag) {
		return in_array("xxta" . $tag, $this->alloc);
	}

	public function addTag($tag) {
		$this->alloc[] = "xxta" . $tag;
	}

	public function removeTag($tag) {
		$this->alloc = array_diff($this->alloc, array("xxta" . $tag));
	}

	/* app methods */

	public function getApps() {
		$result = array();
		foreach ($this->alloc as $alloc) if (substr($alloc, 0, 4) == "xxap") $result[] = substr($alloc, 4);
		return $result;
	}

	public function hasApp($app) {
		return in_array("xxap" . $app, $this->alloc);
	}

	public function addApp($app) {
		$this->alloc[] = "xxap" . $app;
	}

	public function removeApp($app) {
		$this->alloc = array_diff($this->alloc, array("xxap" . $app));
	}
}
