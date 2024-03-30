<?php

namespace Scriptlock\Api;

interface IScriptlock {

	// returns false, if no reason for a lock, true otherwise
	public function check();

	// what to do on lock
	public function lock();

}
