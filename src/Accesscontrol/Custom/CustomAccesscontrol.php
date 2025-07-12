<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Custom;

use Base3\Core\ServiceLocator;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Accesscontrol\Api\IAuthentication;
use Base3\Api\ICheck;

class CustomAccesscontrol implements IAccesscontrol, ICheck {

	private ServiceLocator $servicelocator;
	private mixed $classmap;
	private mixed $userid = null;
	private bool $authenticated = false;

	public function __construct($cnf = null) {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
	}

	public function authenticate(): void {
		if ($this->authenticated) return;
		$this->authenticated = true;

		$verbose = isset($_REQUEST["checkaccesscontrol"]);

		$authentications = $this->classmap->getInstancesByInterface(IAuthentication::class);
		foreach ($authentications as $authentication) $authentication->setVerbose($verbose);

		if ($verbose) echo "=================================<br />LOGOUT<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			$authentication->logout();
		}

		if ($verbose) echo "=================================<br />LOGIN<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			$userid = $authentication->login();
			if ($userid !== null) $this->userid = $userid;
			if ($verbose) echo "&bullet; user: " . ($userid === null ? "null" : $userid) . "<br />";
		}

		if ($verbose) echo "=================================<br />KEEP<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			if ($this->userid !== null) $authentication->keep($this->userid);
		}

		if ($verbose) echo "=================================<br />FINISH<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			$authentication->finish($this->userid);
		}

		if ($verbose) exit;
	}

	public function getUserId(): mixed {
		return $this->userid;
	}

	public function checkDependencies(): array {
		return [
			"depending_services" => $this->classmap === null ? "Fail" : "Ok"
		];
	}
}

