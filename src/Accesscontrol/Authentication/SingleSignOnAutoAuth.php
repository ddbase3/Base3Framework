<?php declare(strict_types=1);

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class SingleSignOnAutoAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $session;
	private $loginpage;
	private $cnf;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->session = $this->servicelocator->get('session');
		$this->loginpage = $this->servicelocator->get('loginpage');
		$this->cnf = $this->servicelocator->get('configuration')->get('base');
	}

	// Implementation of IBase

	public function getName() {
		return "singlesignonautoauth";
	}

	// Implementation of IAuthentication

	public function finish($userid) {
		if ($userid != null
			|| (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
			|| $this->isAjaxRequest()
			|| !$this->session->started()) return;

		if (isset($_SESSION["ssocheck"]) && time() - $_SESSION["ssocheck"] < 60) return;  // nur einmal innerhalb 60s prüfen
		$_SESSION["ssocheck"] = time();

/*
// hat nach autologin falsch weiter geleitet
		$ssocont = strlen($this->cnf["intern"])
			? $this->cnf["url"] . $this->cnf["intern"]
			: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
*/

		$ssocont = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$url = $this->loginpage->getUrl();
		$url .= ( strpos($url, "?") === false ? "?" : "&" ) . "ssocheck&ssocont=" . urlencode((string) $ssocont);
		session_write_close();
		header('Location: ' . $url);
		exit;
	}

	// Header Weiterleitung nicht bei Ajax-Request erlaubt wegen CORS
	private function isAjaxRequest() {
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest";
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->session == null || $this->loginpage == null ? "Fail" : "Ok"
		);
	}

}
