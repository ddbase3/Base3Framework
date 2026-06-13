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

namespace Base3\Language\MultiLang;

use Base3\Api\ICheck;
use Base3\Configuration\Api\IConfiguration;
use Base3\Language\Api\ILanguage;
use Base3\Session\Api\ISession;

class MultiLang implements ILanguage, ICheck {

	private $configuration;
	private $session;

	private $cnf = null;
	private $language = null;
	private $languages = null;

	public function __construct(IConfiguration $configuration, ISession $session) {
		$this->configuration = $configuration;
		$this->session = $session;
	}

	// Implementation of ILanguage

	public function getLanguage(): string {
		if ($this->language !== null) return $this->language;
		if (isset($_SESSION["language"])) {
			$this->language = $_SESSION["language"];
		} else {
			$cnf = $this->getConfig();
			$this->language = $cnf["main"] ?? 'de';
		}
		return $this->language;
	}

	public function setLanguage(string $language) {
		$languages = $this->getLanguages();
		if (!in_array($language, $languages, true)) return;
		$_SESSION["language"] = $this->language = $language;
	}

	public function getLanguages(): array {
		if ($this->languages === null) {
			$cnf = $this->getConfig();
			$this->languages = $cnf["languages"] ?? [];
		}
		return $this->languages;
	}

	// Implementation of ICheck

	public function checkDependencies(): array {
		return [
			"session_started" => $this->session->started() ? "Ok" : "Fail"
		];
	}

	// Private methods

	private function getConfig(): array {
		if ($this->cnf === null) $this->cnf = $this->configuration->get('language') ?? [];
		return $this->cnf;
	}
}

