<?php declare(strict_types=1);

namespace Base3\Xrm;

class XrmEntryAccess {

	public string $mode;		// owner | read | write
	public string $usergroup;	// user | group
	public $id;		// user id/group id

	public static function unserialize($data) {

		// wenn $data schon ein XrmEntryAccess ist, dann direkt zurückgeben
		if (is_object($data) && is_a($data, \Base3\Xrm\XrmEntryAccess::class)) return $data;

		if (is_string($data)) $data = json_decode($data, true);
		if (is_object($data)) $data = (array) $data;

		$xrmentry = new self();
		if (isset($data["mode"])) $xrmentry->mode = $data["mode"];
		if (isset($data["usergroup"])) $xrmentry->usergroup = $data["usergroup"];
		if (isset($data["id"])) $xrmentry->id = $data["id"];

		return $xrmentry;
	}
}
