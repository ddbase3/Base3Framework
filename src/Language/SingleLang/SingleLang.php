<?php declare(strict_types=1);

namespace Base3\Language\SingleLang;

use Base3\Core\ServiceLocator;
use Base3\Language\Api\ILanguage;
use Base3\Api\ICheck;

/* Only use language as configured as "main" */
class SingleLang implements ILanguage, ICheck {

	private $servicelocator;

	private $language;

	public function __construct(\Base3\Configuration\Api\IConfiguration $configuration) {

		// refactoring, former param
		$cnf = null;

		$this->servicelocator = ServiceLocator::getInstance();

		if ($cnf == null) {
			// $configuration = $this->servicelocator->get('configuration');
			if ($configuration != null) $cnf = $configuration->get('language');
		}

		if ($cnf != null) $this->language = $cnf["main"];
	}

	// Implementation of ILanguage

	public function getLanguage(): string {
		return $this->language;
	}

	public function setLanguage(string $language) {
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
