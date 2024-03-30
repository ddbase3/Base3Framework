<?php

namespace Accesscontrol\Api;

use Api\IBase;

interface IAuthentication extends IBase {

	public function setVerbose($verbose);
	public function login();
	public function keep($userid);
	public function finish($userid);
	public function logout();

}
