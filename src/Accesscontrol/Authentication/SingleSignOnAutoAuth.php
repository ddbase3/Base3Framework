<?php declare(strict_types=1);

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
