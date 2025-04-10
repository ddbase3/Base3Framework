<?php declare(strict_types=1);

namespace Base3\Language\MultiLang;

use Base3\Core\ServiceLocator;
use Base3\Language\Api\ILanguage;
use Base3\Api\ICheck;
use Base3\Configuration\Api\IConfiguration;
use Base3\Session\Api\ISession;

class MultiLang implements ILanguage, ICheck {

	private $servicelocator;
	private $session;

	private $cnf;
	private $language;
	private $languages;

	public function __construct(IConfiguration $configuration, ISession $session) {

		$this->servicelocator = ServiceLocator::getInstance();
		$this->session = $session;

		$this->cnf = $configuration->get('language');
		$this->languages = $this->cnf != null && $this->cnf['languages'] != null ? $this->cnf['languages'] : [];

		if (isset($_SESSION["language"])) {
			$this->language = $_SESSION["language"];
		} else {
			if ($this->cnf != null) $this->language = $this->cnf["main"];
		}
	}

	// Implementation of ILanguage

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage(string $language) {
		if (!in_array($language, $this->cnf["languages"])) return;
		$this->language = $_SESSION["language"] = $language;
	}

	public function getLanguages(): array {
		return $this->languages;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"session_started" => $this->session->started() ? "Ok" : "Fail"
		);
	}
}
