<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Authentication;

use Base3\Accesscontrol\AbstractAuth;
use Base3\Api\ICheck;

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
