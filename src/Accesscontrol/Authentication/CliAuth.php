<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Accesscontrol\Authentication;

use Base3\Accesscontrol\AbstractAuth;
use Base3\Api\ICheck;

class CliAuth extends AbstractAuth {

	// Implementation of IBase

	public static function getName(): string {
		return "cliauth";
	}

	// Implementation of IAuthentication

	public function login() {
		return php_sapi_name() == "cli" ? "internal" : null;
	}

}
