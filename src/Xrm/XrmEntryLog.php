<?php

namespace Xrm;

class XrmEntryLog {

	public $action;		// created | changed
	public $user;		// user id
	public $timestamp;	// timestamp Y-m-d H:i:s

	public static function unserialize($data) {

		// wenn $data schon ein XrmEntryLog ist, dann direkt zurückgeben
		if (is_object($data) && is_a($data, 'Xrm\XrmEntryLog')) return $data;

		if (is_string($data)) $data = json_decode($data, 1);
		if (is_object($data)) $data = (array) $data;

		$xrmentry = new self();
		if (isset($data["action"])) $xrmentry->action = $data["action"];
		if (isset($data["user"])) $xrmentry->user = $data["user"];
		if (isset($data["timestamp"])) $xrmentry->timestamp = $data["timestamp"];

		return $xrmentry;
	}

}
