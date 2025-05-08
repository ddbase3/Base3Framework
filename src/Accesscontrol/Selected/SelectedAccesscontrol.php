<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Selected;

use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Accesscontrol\Api\IAuthentication;
use Base3\Api\ICheck;

class SelectedAccesscontrol implements IAccesscontrol, ICheck {

	 /** @var IAuthentication[] */
	private $authentications;

	private $userid = null;

	public function __construct(array $authentications) {

		// Auflösen der Closures (falls aus Container als fn() übergeben)
		$this->authentications = array_map(
			fn($auth) => is_callable($auth) ? $auth() : $auth,
			$authentications
		);

		foreach ($this->authentications as $auth) {
			if ($auth instanceof IAuthentication) continue;
			throw new \InvalidArgumentException("Invalid authentication object");
		}

		$verbose = isset($_REQUEST["checkaccesscontrol"]);
		foreach ($this->authentications as $auth) $auth->setVerbose($verbose);

		if ($verbose) echo "=================================<br />LOGOUT<br />";
		foreach ($this->authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			$auth->logout();
		}

		if ($verbose) echo "=================================<br />LOGIN<br />";
		foreach ($this->authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			$userid = $auth->login();
			if ($userid != null) $this->userid = $userid;
			if ($verbose) echo "&bullet; user: " . ($userid == null ? "null" : $userid) . "<br />";
		}

		if ($verbose) echo "=================================<br />KEEP<br />";
		foreach ($this->authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			if ($this->userid != null) $auth->keep($this->userid);
		}

		if ($verbose) echo "=================================<br />FINISH<br />";
		foreach ($this->authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			$auth->finish($this->userid);
		}

		if ($verbose) exit;
	}

	// Implementation of IAccesscontrol

	public function getUserId() {
		return $this->userid;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"authentication_methods_given" => count($this->authentications) == 0 ? "Fail" : "Ok"
		);
	}
}
