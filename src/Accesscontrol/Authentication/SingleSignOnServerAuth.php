<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class SingleSignOnServerAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $session;
	private $ssotoken;

	private $ssoHashLength;
	private $ssoTimeout;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->session = $this->servicelocator->get('session');
		$this->ssotoken = $this->servicelocator->get('ssotoken');

		$this->ssoHashLength = 64;
		$this->ssoTimeout = 60;

		if (isset($_REQUEST["ssocont"])) {
			$_SESSION["ssoreferer"] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $_REQUEST["ssocont"];
			$_SESSION["ssocont"] = $_REQUEST["ssocont"];
		}
	}

	// Implementation of IBase

	public function getName() {
		return "singlesignonserverauth";
	}

	// Implementation of IAuthentication

	public function finish($userid) {
		// SingleSignOnAuth: $_SESSION["ssocont"] ist gesetzt
		// SingleSignOnAutoAuth: $_REQUEST["ssocheck"] ist gesetzt

		if (isset($_REQUEST["ssocheck"])
				|| ($userid != null && isset($_SESSION["ssocont"]))) {
			$url = $this->getContinueUrl($userid);
			$this->redirectToUrl($url);
		}
	}

	private function getContinueUrl($userid) {
		$url = isset($_SESSION["ssocont"]) ? $_SESSION["ssocont"] : $_SESSION["ssoreferer"];
		if ($userid != null && $this->externDomain()) {
			$ssocode = $this->ssotoken->create("singlesignon", $userid, $this->ssoHashLength, $this->ssoTimeout);
			$url .= ( strpos($url, "?") === false ? "?" : "&" ) . "userid=" . urlencode($userid) . "&ssocode=" . urlencode($ssocode) . "&_continueauth=" . urlencode($_SESSION["ssocont"]);
		}
		unset($_SESSION["ssoreferer"]);
		unset($_SESSION["ssocont"]);
		return $url;
	}

	private function redirectToUrl($url) {
		session_write_close();
		header('Location: ' . $url);
		exit;
	}

	private function externDomain() {
		return true;

		/*
		// nur benötigt, wenn Domain-Session und Cookies domainweit
		$mydomain = "";
		$domparts = explode(".", $_SERVER["SERVER_NAME"]);
		if (sizeof($domparts) >= 2) $mydomain = $domparts[sizeof($domparts) - 2] . "." . $domparts[sizeof($domparts) - 1];
		$contdomain = "";
		$domparts = explode(".", parse_url($_SESSION["ssocont"], PHP_URL_HOST));
		if (sizeof($domparts) >= 2) $contdomain = $domparts[sizeof($domparts) - 2] . "." . $domparts[sizeof($domparts) - 1];
		return $mydomain != $contdomain;
		*/
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->session == null || $this->ssotoken == null ? "Fail" : "Ok"
		);
	}

}
