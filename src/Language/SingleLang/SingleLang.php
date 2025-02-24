<?php declare(strict_types=1);


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

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage(string language) {
		// do nothing
	}

	public function getLanguages(): array {
		return [ $this->language ];
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null ? "Fail" : "Ok"
		);
	}

}
