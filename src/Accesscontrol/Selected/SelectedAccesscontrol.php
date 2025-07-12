<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Selected;

use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Accesscontrol\Api\IAuthentication;
use Base3\Api\ICheck;

class SelectedAccesscontrol implements IAccesscontrol, ICheck {

	private array $authenticationClosures;
	private ?array $authentications = null;
	private mixed $userid = null;
	private bool $authenticated = false;

	public function __construct(array $authentications) {
		$this->authenticationClosures = $authentications;
	}

	private function resolveAuthentications(): array {
		if ($this->authentications !== null) {
			return $this->authentications;
		}

		$this->authentications = array_map(
			fn($auth) => is_callable($auth) ? $auth() : $auth,
			$this->authenticationClosures
		);

		foreach ($this->authentications as $auth) {
			if (!$auth instanceof IAuthentication) {
				throw new \InvalidArgumentException("Invalid authentication object");
			}
		}

		return $this->authentications;
	}

	public function authenticate(): void {
		if ($this->authenticated) return;

		$authentications = $this->resolveAuthentications();
		$verbose = isset($_REQUEST["checkaccesscontrol"]);

		foreach ($authentications as $auth) $auth->setVerbose($verbose);

		if ($verbose) echo "=================================<br />LOGOUT<br />";
		foreach ($authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			$auth->logout();
		}

		if ($verbose) echo "=================================<br />LOGIN<br />";
		foreach ($authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			$userid = $auth->login();
			if ($userid !== null) $this->userid = $userid;
			if ($verbose) echo "&bullet; user: " . ($userid ?? "null") . "<br />";
		}

		if ($verbose) echo "=================================<br />KEEP<br />";
		foreach ($authentications as $auth) {
			if ($verbose) echo "---------------------------------<br />" . $auth->getName() . "<br />";
			if ($this->userid !== null) $auth->keep($this->userid);
		}

		if ($verbose) echo "=================================<br />FINISH<br />";
		foreach ($authentications as $auth) {
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
			"authentication_methods_given" => count($this->authenticationClosures) > 0 ? "Ok" : "Fail"
		];
	}
}

