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

use Base3\Core\ServiceLocator;
use Base3\Accesscontrol\AbstractAuth;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Configuration\Api\IConfiguration;
use Base3\Session\Api\ISession;

class SingleSignOnAutoAuth extends AbstractAuth implements ICheck {

	private $loginpage;

	public function __construct(
		private readonly IConfiguration $configuration,
		private readonly ISession $session,
		private readonly IContainer $container
	) {
		$this->loginpage = $this->container->get('loginpage');
	}

	// Implementation of IBase

	public static function getName(): string {
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
		$this->cnf = $this->configuration->get('base');
		$ssocont = strlen($this->cnf["intern"])
			? $this->cnf["url"] . $this->cnf["intern"]
			: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
*/

		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$uri  = $_SERVER['REQUEST_URI'] ?? '/';
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
		$ssocont = $scheme . '://' . $host . $uri;

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
