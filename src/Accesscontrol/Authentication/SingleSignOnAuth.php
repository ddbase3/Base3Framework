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
use Base3\Token\Api\IToken;

class SingleSignOnAuth extends AbstractAuth implements ICheck {

	private $ssoHashLength;
	private $ssoTimeout;

	public function __construct(private IToken $ssotoken) {}

	// Implementation of IBase

	public static function getName(): string {
		return "singlesignonauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if (!isset($_REQUEST["userid"]) || !isset($_REQUEST["ssocode"])) return null;

		$userid = $_REQUEST["userid"];
		$ssocode = $_REQUEST["ssocode"];
		if (!$this->ssotoken->check("singlesignon", $userid, $ssocode)) return null;
		$this->ssotoken->delete("singlesignon", $userid, $ssocode);

		return $userid;
	}

	public function finish($userid) {
		/*
		$getvars = $_GET;  // new version $this->request->allGet();
		unset($getvars["userid"]);
		unset($getvars["ssocode"]);
		$url = strtok($_SERVER["REQUEST_URI"], '?') . "?" . http_build_query($getvars);
		*/
		// $url = strtok($_SERVER["REQUEST_URI"], '?');

/*
		if (!isset($_REQUEST["userid"]) || !isset($_REQUEST["ssocode"])) return;

		$url = $_REQUEST["ssocont"];

		session_write_close();
		header('Location: ' . $url);
		exit;
*/
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->ssotoken == null ? "Fail" : "Ok"
		);
	}

}
