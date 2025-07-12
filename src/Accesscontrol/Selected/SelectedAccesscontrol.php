<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Selected;

use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Accesscontrol\Api\IAuthentication;
use Base3\Api\ICheck;

class SelectedAccesscontrol implements IAccesscontrol, ICheck {

	private array $authentications;
	private mixed $userid = null;
	private bool $authenticated = false;

	public function __construct(array $authentications) {
		// Resolve closures if given
		$this->authentications = array_map(
			fn($auth) => is_callable($auth) ? $auth() : $auth,
			$authentications
		);

		foreach ($this->authentications as $auth) {
			if (!$auth instanceof IAuthentication) {
				throw new \InvalidArgumentException("Invalid authentication object");
			}
		}
	}

	public function authenticate(): void {
		if ($this->authenticated) return;

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
			if ($userid !== null) $this->userid = $userid;
			if ($verbose) echo "&bullet; user: " . ($userid ?? "null") . "<br />";
		}

		if ($verbose) echo "=================================<br />KEEP<br />";
		foreach ($this->authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			if ($this->userid !== null) $auth->keep($this->userid);
		}

		if ($verbose) echo "=================================<br />FINISH<br />";
		foreach ($this->authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			$auth->finish($this->userid);
		}

		if ($verbose) exit;

		$this->authenticated = true;
	}

	public function getUserId(): mixed {
		return $this->userid;
	}

	public function checkDependencies(): array {
		return [
			"authentication_methods_given" => count($this->authentications) > 0 ? "Ok" : "Fail"
		];
	}
}

