<?php

namespace Language\SingleLang;

use Language\Api\ILanguage;
use Api\ICheck;

/* Only use language as configured as "main" */
class SingleLang implements ILanguage, ICheck {

	private $servicelocator;

	private $language;

	public function __construct($cnf = null) {

		$this->servicelocator = \Base3\ServiceLocator::getInstance();

		if ($cnf == null) {
			$configuration = $this->servicelocator->get('configuration');
			if ($configuration != null) $cnf = $configuration->get('language');
		}

		if ($cnf != null) $this->language = $cnf["main"];
	}

	// Implementation of ILanguage

	public function getLanguage() {
		return $this->language;
	}

	public function setLanguage($language) {
		// do nothing
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null ? "Fail" : "Ok"
		);
	}

}
