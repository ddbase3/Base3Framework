<?php declare(strict_types=1);

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class CliAuth extends AbstractAuth {

	// Implementation of IBase

	public function getName() {
		return "cliauth";
	}

	// Implementation of IAuthentication

	public function login() {
		return php_sapi_name() == "cli" ? "internal" : null;
	}

}
