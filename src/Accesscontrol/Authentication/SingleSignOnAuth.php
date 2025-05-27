<?php declare(strict_types=1);

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
