<?php

namespace Accesscontrol;

use Accesscontrol\Api\IAuthentication;

abstract class AbstractAuth implements IAuthentication {

	protected $verbose;

	// Implementation of IAuthentication

	public function setVerbose($verbose) {
		$this->verbose = $verbose;
	}

	public function login() {}

	public function keep($userid) {}

	public function finish($userid) {}

	public function logout() {}

}
