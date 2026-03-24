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

