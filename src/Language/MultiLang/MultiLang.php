<?php declare(strict_types=1);

namespace Language\MultiLang;

use Base3\ServiceLocator;
use Language\Api\ILanguage;
use Api\ICheck;

class MultiLang implements ILanguage, ICheck {

	private $cnf;
	private $language;
	private $languages;

	public function __construct($cnf = null) {
		$servicelocator = ServiceLocator::getInstance();

		$configuration = $servicelocator->get('configuration');
		if ($configuration != null)
			$this->cnf = $configuration->get('language');

		$this->languages = $this->cnf != null && $this->cnf['languages'] != null ? $this->cnf['languages'] : [];

		if (isset($_SESSION["language"])) {
			$this->language = $_SESSION["language"];
		} else {
			if ($this->cnf != null) $this->language = $this->cnf["main"];
		}
	}

	// Implementation of ILanguage

	public function getLanguage() {
		return $this->language;
	}

	public function setLanguage($language) {
		if (!in_array($language, $this->cnf["languages"])) return;
		$this->language = $_SESSION["language"] = $language;
	}

	public function getLanguages() {
		return $this->languages;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null || $this->servicelocator->get('session') == null ? "Fail" : "Ok"
		);
	}

}
