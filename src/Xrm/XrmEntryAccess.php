<?php

namespace Xrm;

class XrmEntryAccess {

	public $mode;		// owner | read | write
	public $usergroup;	// user | group
	public $id;		// user id/group id

	public static function unserialize($data) {

		// wenn $data schon ein XrmEntryAccess ist, dann direkt zurückgeben
		if (is_object($data) && is_a($data, 'Xrm\XrmEntryAccess')) return $data;

		if (is_string($data)) $data = json_decode($data, 1);
		if (is_object($data)) $data = (array) $data;

		$xrmentry = new self();
		if (isset($data["mode"])) $xrmentry->mode = $data["mode"];
		if (isset($data["usergroup"])) $xrmentry->usergroup = $data["usergroup"];
		if (isset($data["id"])) $xrmentry->id = $data["id"];

		return $xrmentry;
	}

}
