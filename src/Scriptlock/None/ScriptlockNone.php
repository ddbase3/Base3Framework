<?php

namespace Scriptlock\None;

use Scriptlock\Api\IScriptlock;

class ScriptlockNone implements IScriptlock {

	public function __construct() {
	}

	// Implementation of IScriptlock

	public function check() {
		return false;
	}

	public function lock() {
	}

}
