<?php

namespace Language\MultiLang;

use Language\Api\ILanguage;
use Api\ICheck;

class MultiLang implements ILanguage, ICheck {

	private $servicelocator;

	private $cnf;
	private $language;

	public function __construct($cnf = null) {

		$this->servicelocator = \Base3\ServiceLocator::getInstance();

		if ($cnf == null) {
			$configuration = $this->servicelocator->get('configuration');
			if ($configuration != null) $this->cnf = $configuration->get('language');
		}

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

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null || $this->servicelocator->get('session') == null ? "Fail" : "Ok"
		);
	}

}
